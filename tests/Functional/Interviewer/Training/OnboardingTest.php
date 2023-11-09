<?php

namespace App\Tests\Functional\Interviewer\Training;

use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Interviewer;
use App\Entity\InterviewerTrainingRecord;
use App\Entity\User;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\OnBoarding\AbstractOtpTest;
use App\Utility\TravelDiary\SerialHelper;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\ServerExtension;

class OnboardingTest extends AbstractOtpTest
{
    private const PASSCODE = '12345678';
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            UserFixtures::class
        ]);
        $this->passcodeGenerator = self::getContainer()->get(PasscodeGenerator::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        try {
            $this->client->clickLink('Logout');
        } catch(\Throwable $e) {}
        parent::tearDown();
    }

    public function testUnsignedOtpLogin(): void
    {
        $password = $this->passcodeGenerator->getPasswordForUserIdentifier(self::PASSCODE);

        $this->loginOtpUser(self::PASSCODE, $password);
        self::assertNotEmpty($this->client->getCrawler()->filter('#otp_login_group-error'));
    }

    public function testOnBoarding(): void
    {
        $this->loginUser('interviewer@example.com', 'password');
        $this->client->get('/interviewer/training');

        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_NEW);

        // find onboarding link
        $this->client->clickLink('Module 5: Onboarding practice');

        // find onboarding link
        $this->client->clickLink('Access onboarding training');

        // submit otp login form
        $this->client->submitForm('otp_login[sign_in]', [
            'otp_login[group][identifier]' => self::PASSCODE,
            'otp_login[group][passcode]' => $this->passcodeGenerator->getPasswordForUserIdentifier(self::PASSCODE),
        ]);
        $this->assertEquals('/onboarding/household/introduction', $this->getUrlPath());
        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_IN_PROGRESS);

        $this->client->submitForm('form[button_group][continue]');
        $this->assertEquals('/onboarding/household/details', $this->getUrlPath());

        $now = new \DateTime();
        $this->client->submitForm('household[button_group][continue]', [
            'household[addressNumber]' => '1',
            'household[householdNumber]' => '1',
            'household[checkLetter]' => SerialHelper::getCheckLetter(AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL, 1, 1),
            'household[diaryWeekStartDate][day]' => $now->format('d'),
            'household[diaryWeekStartDate][month]' => $now->format('m'),
            'household[diaryWeekStartDate][year]' => $now->format('Y'),
        ]);
        $this->takeScreenshotIfTestFailed();
//        ServerExtension::takeScreenshots('info', 'onboarding');
        $this->assertEquals('/onboarding', $this->getUrlPath());

        // add a DK
        $this->client->clickLink('Add a household member');
        $this->assertEquals('/onboarding/diary-keeper/add/details', $this->getUrlPath());

        $this->client->submitForm('details[button_group][continue]', [
            'details[name]' => 'dk 1',
            'details[number]' => '1',
            'details[isAdult]' => 'true',
        ]);
        $this->assertEquals('/onboarding/diary-keeper/add/identity', $this->getUrlPath());

        /** @var DiaryKeeper $diaryKeeperAdult */
        $diaryKeeperAdult = $this->getFixtureByReference('diary-keeper:adult');
        // User the same user identifier as an existing DiaryKeeper, to ensure uniqueness is relative
        // to the training interviewer
        $this->client->submitForm('user_identifier[button_group][continue]', [
            'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
            'user_identifier[user][username]' => $diaryKeeperAdult->getUser()->getUserIdentifier(),
        ]);
        $this->assertEquals('/onboarding/diary-keeper/add/add-another', $this->getUrlPath());

        $this->client->submitForm('add_another[button_group][continue]', [
            'add_another[add_another]' => 'true',
        ]);
        $this->assertEquals('/onboarding/diary-keeper/add/details', $this->getUrlPath());

        // add DK 2
        $this->client->submitForm('details[button_group][continue]', [
            'details[name]' => 'dk 2',
            'details[number]' => '2',
            'details[isAdult]' => 'true',
        ]);
        $this->assertEquals('/onboarding/diary-keeper/add/identity', $this->getUrlPath());
        $this->client->submitForm('user_identifier[button_group][continue]', [
            'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
            'user_identifier[user][username]' => 'dk.2@example.com',
        ]);
        $this->assertEquals('/onboarding/diary-keeper/add/add-another', $this->getUrlPath());

        $this->client->submitForm('add_another[button_group][continue]', [
            'add_another[add_another]' => 'false',
        ]);
        $this->assertEquals('/onboarding', $this->getUrlPath());

        // add a vehicle
        $this->client->clickLink('Add a vehicle');
        $this->assertEquals('/onboarding/vehicle/add', $this->getUrlPath());

        $elements = $this->client->findElements(WebDriverBy::xpath('//input[@id="vehicle_primaryDriver_0"]'));
        $this->client->submitForm('vehicle[button_group][save]', [
            'vehicle[friendlyName]' => 'a car',
            'vehicle[capiNumber]' => '1',
            'vehicle[method]' => '2', // car
            'vehicle[primaryDriver]' => $elements[0]->getAttribute('value'),
        ]);
        $this->assertEquals('/onboarding', $this->getUrlPath());

        // submit
        $this->client->clickLink('Submit household');
        $this->assertEquals('/onboarding/submit', $this->getUrlPath());

        $this->client->submitForm('confirm_household[button_group][confirm]');
        $this->assertEquals('/onboarding', $this->getUrlPath());

        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_COMPLETE);

        // ensure messenger_messages table is empty / notify messages not sent
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $messages = $entityManager->getConnection()->fetchAssociative('SELECT * FROM messenger_messages WHERE queue_name = ?', ['govuk-notify']);
        $this->assertEmpty($messages, 'Notify messages being sent');
    }

    protected function assertModuleHasState($expectedState)
    {
        $user = $this->entityManager->getRepository(User::class)->loadUserByIdentifier('interviewer@example.com');
        $trainingRecord = $user->getInterviewer()->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_ONBOARDING_PRACTICE);
        $this->entityManager->refresh($trainingRecord);
        $this->assertEquals($expectedState, $trainingRecord->getState());
    }
}