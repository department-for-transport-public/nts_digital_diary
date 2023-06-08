<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestCallbackAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use Symfony\Component\DomCrawler\Crawler;

class DiaryKeeperCrashTest extends AbstractDiaryKeeperTest
{
    public function wizardData(): array
    {
        return [
            'Onboarding: should not crash when adding DiaryKeeper ' => [
                [
                    new FormTestAction(
                        '/onboarding/diary-keeper/add/details',
                        'details_button_group_continue',
                        [
                            new FormTestCase([
                                'details[name]' => 'One',
                                'details[number]' => '1',
                                'details[isAdult]' => 'true',
                            ]),
                        ]),
                    new FormTestAction(
                        '/onboarding/diary-keeper/add/identity',
                        'user_identifier_button_group_continue',
                        [
                            new FormTestCase([
                                'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                                'user_identifier[user][username]' => 'one@example.com',
//                                'user_identifier[user][consent]' => '1',
                            ])
                        ]),
                    new CallbackAction($this->fetchDiaryKeeperAndStoreId(['name' => 'One'], 'diary-keeper-one')),
                    new FormTestAction(
                        '/onboarding/diary-keeper/add/add-another',
                        'add_another_button_group_continue',
                        [
                            new FormTestCase(['add_another[add_another]' => 'true']),
                        ]),
                    new FormTestAction(
                        '/onboarding/diary-keeper/add/details',
                        'details_button_group_continue',
                        [
                            new FormTestCase([
                                'details[name]' => 'Two',
                                'details[number]' => '2',
                                'details[isAdult]' => 'true',
                            ]),
                        ]),
                    new FormTestCallbackAction(
                        '/onboarding/diary-keeper/add/identity',
                        'user_identifier_button_group_continue',
                        function(Context $context) {
                            return [
                                new FormTestCase([
                                    'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                                    'user_identifier[user][username]' => 'two',
//                                    'user_identifier[user][consent]' => '1',
                                    'user_identifier[user][proxies][]' => [
                                        $context->get('diary-keeper-one'),
                                    ],
                                ], ['#user_identifier_user_username'])
                            ];
                        }),
                    new FormTestAction(
                        '/onboarding/diary-keeper/add/identity',
                        'user_identifier_button_group_continue',
                        [
                                new FormTestCase([
                                    'user_identifier[mediaType]' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                                    'user_identifier[user][username]' => 'two',
//                                    'user_identifier[user][consent]' => '1',
                                    'user_identifier[user][proxies][]' => [],
                                ], [], null, true)
                        ]),
                    new CallbackAction(function(Context $context) {
                        $pageSource = $context->getClient()->getPageSource();

                        // Tried to do this directly via $context->getClient()->getCrawler(), but that didn't work :/
                        $crawler = new Crawler($pageSource);
                        $title = $crawler->filter('title')->text();

                        $testCase = $context->getTestCase();

                        $testCase->assertStringNotContainsString('(500 Internal Server Error)', $title);
                    }),
                ],
            ],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testOnboardingDiaryKeeperWizard(array $wizardData)
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);
        $identifier = self::USER_IDENTIFIER; // OTP user where address #, household #, and diary week start date already entered
        $this->loginOtpUser($identifier, $this->passcodeGenerator->getPasswordForUserIdentifier($identifier));

        $this->clickLinkContaining('Add a diary keeper');
        $this->doWizardTest($wizardData);
    }
}