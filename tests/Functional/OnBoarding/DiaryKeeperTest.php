<?php

namespace App\Tests\Functional\OnBoarding;

use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;

class DiaryKeeperTest extends AbstractDiaryKeeperTest
{
    protected function generateTest(bool $doEdit, bool $overwriteDetails, bool $isAdult): array
    {
        $firstName = 'Bonzo';
        $firstCapiNumber = '1';
        $firstEmail = 'bonzo@example.com';

        $secondName = 'Fluffy';
        $secondCapiNumber = '2';
        $secondEmail = 'fluffy@example.com';

        $tests = $this->getAddDiaryKeeperTests($firstName, $firstCapiNumber, $isAdult, $firstEmail, false);

        $tests[] = new CallbackAction($this->fetchDiaryKeeperAndStoreId(['name' => $firstName], 'diary-keeper'));

        $tests[] = new PathTestAction('/onboarding');

        $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck($firstName, $firstCapiNumber, $isAdult, $firstEmail));

        if ($doEdit) {
            $tests[] = new CallbackAction($this->clickDiaryKeeperEditLink($firstName));

            $tests[] = new FormTestAction(
                '#^\/onboarding\/diary-keeper\/[A-Z0-9]+\/edit\/details$#',
                'details_button_group_continue',
                $this->getFirstStepTestCases(true, $overwriteDetails, $secondName, $secondCapiNumber, !$isAdult),
                [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

            $tests[] = new PathTestAction('/onboarding');

            $expectedName = $overwriteDetails ? $secondName : $firstName;
            $expectedCapiNumber = $overwriteDetails ? $secondCapiNumber : $firstCapiNumber;
            $expectedIsAdult = $overwriteDetails ? !$isAdult : $isAdult;

            $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck($expectedName, $expectedCapiNumber, $expectedIsAdult, $firstEmail));

            $tests[] = new CallbackAction($this->clickDiaryKeeperEditLink($expectedName, $firstEmail));

            $tests[] = new FormTestAction(
                '#^\/onboarding\/diary-keeper\/[A-Z0-9]+\/edit\/identity#',
                'user_identifier_button_group_continue',
                $this->getSecondStepTestCases( $overwriteDetails, $secondEmail),
                [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

            $tests[] = new PathTestAction('/onboarding');

            $expectedEmail = $overwriteDetails ? $secondEmail : $firstEmail;

            $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck($expectedName, $expectedCapiNumber, $expectedIsAdult, $expectedEmail));
        }

        return [$tests];
    }

    public function wizardData(): array
    {
        return [
            // Not sure if separate tests for adult/child makes any tangible difference, so just using isAdult for the moment
            'Onboarding: add + edit diary-keeper' => $this->generateTest(true, false, true),
            'Onboarding: add + edit diary-keeper w/details overwrite' => $this->generateTest(true, true, true),
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testOnboardingDiaryKeeperWizard(array $wizardData)
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class, UserFixtures::class]);
        $identifier = self::USER_IDENTIFIER; // OTP user where address #, household #, and diary week start date already entered
        $this->loginOtpUser($identifier, $this->passcodeGenerator->getPasswordForUserIdentifier($identifier));

        $this->clickLinkContaining('Add a diary keeper');
        $this->doWizardTest($wizardData);
    }
}