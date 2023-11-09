<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestCallbackAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;

class DiaryKeeperWithProxyTest extends AbstractDiaryKeeperTest
{
    protected function generateTest(bool $overwriteDetails, bool $isAdult): array
    {
        $proxyName = 'Proxx';
        $proxyCapiNumber = '3';
        $proxyEmail = 'proxx@example.com';

        $firstName = 'Bonzo';
        $firstCapiNumber = '1';
        $firstEmail = 'bonzo@example.com';

        $secondName = 'Fluffy';
        $secondCapiNumber = '2';
        $secondEmail = 'fluffy@example.com';

        $tests = [];

        // 1. Add a diary keeper ($proxyName, $proxyCapiNumber, adult: yes, email: $proxyEmail) and choose addAnother: yes
        $tests = array_merge($tests, $this->getAddDiaryKeeperTests($proxyName, $proxyCapiNumber, true, $proxyEmail, true));

        // 2. Store the ID of the newly created DK into the context
        $tests[] = new CallbackAction($this->fetchDiaryKeeperAndStoreId(['name' => $proxyName], 'proxy-diary-keeper'));

        // 3. Add another diary keeper ($firstName, $firstCapiNumber, $isAdult, proxy: $proxyDiaryKeeperId) and choose addAnother: no
        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/details',
            'details_button_group_continue',
            $this->getFirstStepTestCases(false, true, $firstName, $firstCapiNumber, $isAdult));

        $tests[] = new FormTestCallbackAction(
            '/onboarding/diary-keeper/add/identity',
            'user_identifier_button_group_continue',
            $this->getSecondStepWithProxyTestCases(
                false,
                true,
                null,
                ['proxy-diary-keeper'],
            ));

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/add-another',
            'add_another_button_group_continue',
            [
                new FormTestCase([], ['#add_another_add_another']),
                new FormTestCase(['add_another[add_another]' => 'false']),
            ],
        );

        // 4. Store the ID of the newly created DK into $diaryKeeperId
        $diaryKeeperId = '';
        $tests[] = new CallbackAction($this->fetchDiaryKeeperAndStoreId(['name' => $firstName], 'diary-keeper'));

        // 5. Check we were returned to /onboarding
        $tests[] = new PathTestAction('/onboarding');

        // 6. Check that the diary keeper was created as expected
        $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck($firstName, $firstCapiNumber, $isAdult, null, ['proxy-diary-keeper']));

        // 7. Click the "change" link for the diary keeper with name $firstName, on their "name" row
        $tests[] = new CallbackAction($this->clickDiaryKeeperEditLink($firstName));

        // 8. Overwrite the diary keeper details ($secondName, $secondCapiNumber, adult: false, email: $secondEmail)
        $tests[] = new FormTestAction(
            '#^\/onboarding\/diary-keeper\/[A-Z0-9]+\/edit\/details$#',
            'details_button_group_continue',
            $this->getFirstStepTestCases(true, $overwriteDetails, $secondName, $secondCapiNumber, !$isAdult),
            [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        // 9. Check we were returned to /onboarding
        $tests[] = new PathTestAction('/onboarding');

        $expectedName = $overwriteDetails ? $secondName : $firstName;
        $expectedCapiNumber = $overwriteDetails ? $secondCapiNumber : $firstCapiNumber;
        $expectedIsAdult = $overwriteDetails ? !$isAdult : $isAdult;

        // 10. Check that the diary keeper was updated as expected
        $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck($expectedName, $expectedCapiNumber, $expectedIsAdult, null, ['proxy-diary-keeper']));

        // 11. Click the "change" link for the diary keeper with name $expectedName, on their "proxy" row
        $tests[] = new CallbackAction($this->clickDiaryKeeperEditLink($expectedName, $proxyName));

        // 12. Overwrite the diary keeper details (proxy: false, email: $secondEmail)
        $tests[] = new FormTestCallbackAction(
            '#^\/onboarding\/diary-keeper\/[A-Z0-9]+\/edit\/identity#',
            'user_identifier_button_group_continue',
            $this->getSecondStepWithProxyTestCases(true, $overwriteDetails, $secondEmail, []),
            [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        // 13. Check we were returned to /onboarding
        $tests[] = new PathTestAction('/onboarding');

        // 14. Check that the diary keeper was updated as expected
        if ($overwriteDetails) {
            $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck($expectedName, $expectedCapiNumber, $expectedIsAdult, $secondEmail, []));
        } else {
            $tests[] = new CallbackAction($this->diaryKeeperDatabaseCheck($expectedName, $expectedCapiNumber, $expectedIsAdult, null, ['proxy-diary-keeper']));
        }

        return [$tests];
    }

    protected function getSecondStepWithProxyTestCases(bool $isEdit, bool $overwriteDetails, ?string $email, array $proxyKeys=[]): callable
    {
        return function(Context $context)
        use($isEdit, $overwriteDetails, $email, $proxyKeys)
        {
            if ($overwriteDetails) {
                $tests = [];

                $tests[] = new FormTestCase([
                    'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user_identifier[user][username]' => '',
//                    'user_identifier[user][consent]' => '',
                    'user_identifier[user][proxies][]' => [],
                ], ['#user_identifier_user']);

                $tests[] = new FormTestCase([
                    'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user_identifier[user][username]' => 'Silly',
//                    'user_identifier[user][consent]' => '',
                    'user_identifier[user][proxies][]' => [],
                ], [
                    '#user_identifier_user_username',
//                    '#user_identifier_user_consent'
                ]);

                $tests[] = new FormTestCase([
                    'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user_identifier[user][username]' => 'Not@Email',
//                    'user_identifier[user][consent]' => '',
                    'user_identifier[user][proxies][]' => [],
                ], [
                    '#user_identifier_user_username',
//                    '#user_identifier_user_consent'
                ]);

                $tests[] = new FormTestCase([
                    'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user_identifier[user][username]' => 'Not@Email',
//                    'user_identifier[user][consent]' => '',
                    'user_identifier[user][proxies][]' => [],
                ], [
                    '#user_identifier_user_username',
//                    '#user_identifier_user_consent'
                ]);

//                $tests[] = new FormTestCase([
//                'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
//                    'user_identifier[user][username]' => 'test@example.com',
//                    'user_identifier[user][consent]' => '',
//                    'user_identifier[user][proxies][]' => [],
//                ], ['#user_identifier_user_consent']);

                if ($email === null && empty($proxyKeys)) {
                    throw new \RuntimeException('Either $email or $proxyKeys should be set');
                }

                $formData = [
                    'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user_identifier[user][username]' => $email ?? '',
//                    'user_identifier[user][consent]' => $email ? '1' : '',
                    'user_identifier[user][proxies][]' => [],
                ];

                if ($proxyKeys) {
                    $proxyChoices = array_map(fn($k) => $context->get($k), $proxyKeys);
                    $formData['user_identifier[user][proxies][]'] = $proxyChoices;
                }

                $tests[] = new FormTestCase($formData);

                return $tests;
            } else {
                return [
                    new FormTestCase([]),
                ];
            }
        };
    }

    public function wizardData(): array
    {
        return [
            // Not sure if separate tests for adult/child makes any tangible difference, so just using isAdult for the moment
            'Onboarding: Existing DK, then add + edit diary-keeper ' => $this->generateTest( false, true),
            'Onboarding: Existing DK, then add + edit diary-keeper w/details overwrite' => $this->generateTest(true, true),
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