<?php

namespace App\Tests\Functional\Misc;

use App\Entity\DiaryKeeper;
use App\Entity\OtpUser;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractFormWizardController;
use Symfony\Component\Panther\Client;

class UniqueConstraintWizardTestTest extends AbstractProceduralWizardTest
{
    protected Client $clientA;
    protected Client $clientB;

    protected Context $contextA;
    protected Context $contextB;
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);
        $this->clientA = $this->client;
        $this->clientB = static::createAdditionalPantherClient();

        $passcodeGenerator = self::getContainer()->get(PasscodeGenerator::class);
        $this->client = $this->clientA;
        $this->contextA = $this->createContext('/onboarding/diary-keeper');
        $this->client = $this->clientB;
        $this->contextB = $this->createContext('/onboarding/diary-keeper');

        $otpUser = $this->fixtureReferenceRepository->getReference('user:otp:partially-onboarded', OtpUser::class);
        $this->client = $this->clientA;
        $this->loginOtpUser($otpUser->getUserIdentifier(), $passcodeGenerator->getPasswordForUserIdentifier($otpUser->getUserIdentifier()));
        $this->client = $this->clientB;
        $this->loginOtpUser($otpUser->getUserIdentifier(), $passcodeGenerator->getPasswordForUserIdentifier($otpUser->getUserIdentifier()));
    }

    public function testUniqueConstraintViolation(): void
    {
        $this->clientA->request('GET', '/onboarding/diary-keeper/add/details');

        $this->client = $this->clientA;
        $this->context = $this->contextA;
        $this->formTestAction(
            '/add/details',
            'details_button_group_continue',
            [
                new FormTestCase([
                    'details[name]' => 'Alice',
                    'details[number]' => '1',
                    'details[isAdult]' => 'true',
                ])
            ]
        );

        $this->clientB->request('GET', '/onboarding/diary-keeper/add/details');
        $this->client = $this->clientB;
        $this->context = $this->contextB;
        $this->formTestAction(
            '/add/details',
            'details_button_group_continue',
            [
                new FormTestCase([
                    'details[name]' => 'Alice',
                    'details[number]' => '1',
                    'details[isAdult]' => 'true',
                ])
            ]
        );

        $this->formTestAction(
            '/add/identity',
            'user_identifier_button_group_continue',
            [
                new FormTestCase([
                    'user_identifier[mediaType]' => 'other',
                    'user_identifier[interviewerMediaType]' => DiaryKeeper::MEDIA_TYPE_PAPER,
                ]),
            ]
        );
        $this->doAssertPathMatches('/onboarding/diary-keeper/add/add-another', false);

        $this->client = $this->clientA;
        $this->context = $this->contextA;
        $this->formTestAction(
            '/add/identity',
            'user_identifier_button_group_continue',
            [
                new FormTestCase([
                    'user_identifier[mediaType]' => 'other',
                    'user_identifier[interviewerMediaType]' => DiaryKeeper::MEDIA_TYPE_PAPER,
                ]),
            ]
        );

        $this->doAssertPathMatches('/onboarding', false);
        // This assertion only works on the first client, so the test must use the first client
        // as the one which gets the unique constraint exception
        $this->assertSelectorExists('#' . AbstractFormWizardController::UNIQUE_CONSTRAINT_NOTIFICATION_ID);
    }
}