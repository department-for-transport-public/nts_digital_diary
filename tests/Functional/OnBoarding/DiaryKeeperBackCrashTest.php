<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use Symfony\Component\DomCrawler\Crawler;

class DiaryKeeperBackCrashTest extends AbstractDiaryKeeperTest
{
    public function wizardData(): array
    {
        return [
            'Onboarding: should not crash when adding DiaryKeeper (back-button)' => [
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
                            ])
                        ]),
                    new CallbackAction($this->fetchDiaryKeeperAndStoreId(['name' => 'One'], 'diary-keeper-one')),
                    new CallbackAction(function(Context $context) {
                        $client = $context->getClient();

                        $client->back();

                        $crawler = new Crawler($client->getPageSource());
                        $title = $crawler->filter('title')->text();

                        // If this is the case, it means that the wizard was reset, because this is the first page of the wizard.
                        // Previously, we saw the last page of the wizard, and hitting submit would cause an error.

                        $context->getTestCase()->assertStringContainsString('Add household member — details', $title);
                    }),
                ],
            ],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testOnboardingDiaryKeeperBackCrash(array $wizardData)
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);
        $identifier = self::USER_IDENTIFIER; // OTP user where address #, household #, and diary week start date already entered
        $this->loginOtpUser($identifier, $this->passcodeGenerator->getPasswordForUserIdentifier($identifier));

        $this->clickLinkContaining('Add a household member');
        $this->doWizardTest($wizardData);
    }
}