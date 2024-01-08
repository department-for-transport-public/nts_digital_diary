<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;

class DiaryKeeperAddAnotherTest extends AbstractDiaryKeeperTest
{
    protected function generateTest(): array
    {
        $tests = [];

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/details',
            'details_button_group_continue',
            [new FormTestCase(['details[name]' => 'Person one', 'details[number]' => 1, 'details[isAdult]' => 'true'])]
        );

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/identity',
            'user_identifier_button_group_continue',
            [new FormTestCase(['user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL, 'user_identifier[user][username]' => 'one@example.com'])]
        );

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/add-another',
            'add_another_button_group_continue',
            [new FormTestCase(['add_another[add_another]' => 'true'])],
        );

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/details',
            'details_button_group_continue',
            [new FormTestCase(['details[name]' => 'Person two', 'details[number]' => 2, 'details[isAdult]' => 'true'])]
        );

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/identity',
            'user_identifier_button_group_continue',
            [new FormTestCase(['user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL, 'user_identifier[user][username]' => 'two@example.com'])]
        );

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/add-another',
            'add_another_button_group_continue',
            [new FormTestCase(['add_another[add_another]' => 'false'])],
        );

        $tests[] = new PathTestAction('/onboarding');

        $tests[] = new CallbackAction($this->fetchDiaryKeeperAndStoreId(['name' => 'Person one'], 'diary-keeper'));
        $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck('Person one', 1, true, 'one@example.com'));

        $tests[] = new CallbackAction($this->fetchDiaryKeeperAndStoreId(['name' => 'Person two'], 'diary-keeper'));
        $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck('Person two', 2, true, 'two@example.com'));

        return [$tests];
    }

    public function wizardData(): array
    {
        return [
            'Onboarding: diary-keeper add-another functionality' => $this->generateTest(),
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

        $this->clickLinkContaining('Add a household member');

        $this->doWizardTest($wizardData);
    }
}