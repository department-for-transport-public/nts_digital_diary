<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Entity\OtpUser;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\DataFixtures\TestSpecific\DiaryKeeperForDuplicateUsernameFixtures;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;
use App\Tests\Functional\Wizard\Form\FormTestCase;

class DuplicateUsernameTest extends AbstractProceduralWizardTest
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class, UserFixtures::class, DiaryKeeperForDuplicateUsernameFixtures::class]);
        $passcodeGenerator = self::getContainer()->get(PasscodeGenerator::class);
        $this->context = $this->createContext('/onboarding/diary-keeper');

        /** @var OtpUser $otpUser */
        $otpUser = $this->getFixtureByReference('user:otp:partially-onboarded');
        $this->loginOtpUser($otpUser->getUserIdentifier(), $passcodeGenerator->getPasswordForUserIdentifier($otpUser->getUserIdentifier()));
    }

    public function dataUserReferences(): array
    {
        return [
            'From different household' => ['diary-keeper:adult'],
            'From same household' => ['diary-keeper:duplicate'],
        ];
    }

    /**
     * @dataProvider dataUserReferences
     */
    public function testDuplicateUsername(string $existingUserReference)
    {
        $this->client->request('GET', '/onboarding/diary-keeper/add/details');

        $this->formTestAction(
            '/add/details',
            'details_button_group_continue',
            [
                new FormTestCase([
                    'details[name]' => 'duplicate',
                    'details[number]' => '2',
                    'details[isAdult]' => 'true',
                ])
            ]
        );

        /** @var DiaryKeeper $existingDiaryKeeper */
        $existingDiaryKeeper = $this->getFixtureByReference($existingUserReference);

        $this->formTestAction(
            '/add/identity',
            'user_identifier_button_group_continue',
            [
                new FormTestCase([
                    'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user_identifier[user][username]' => $existingDiaryKeeper->getUser()->getUserIdentifier(),
                ], [
                    '#user_identifier_user_username',
                ]),
            ]
        );
    }
}