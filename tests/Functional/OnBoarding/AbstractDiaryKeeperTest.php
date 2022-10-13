<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;

abstract class AbstractDiaryKeeperTest extends AbstractOtpTest
{
    const USER_IDENTIFIER = '2345678901';

    protected function getAddDiaryKeeperTests(string $name, string $capiNumber, bool $isAdult, string $email, bool $addAnother): array
    {
        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/details',
            'details_button_group_continue',
            $this->getFirstStepTestCases(false, true, $name, $capiNumber, $isAdult));

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/identity',
            'user_identifier_button_group_continue',
            $this->getSecondStepTestCases(true, $email));

        $tests[] = new FormTestAction(
            '/onboarding/diary-keeper/add/add-another',
            'add_another_button_group_continue',
            [
                new FormTestCase([], ['#add_another_add_another']),
                new FormTestCase(['add_another[add_another]' => ($addAnother ? 'true' : 'false')]),
            ],
        );
        return $tests;
    }

    protected function getFirstStepTestCases(bool $isEdit, bool $overwriteDetails, string $name, string $capiNumber, bool $isAdult): array
    {
        if ($overwriteDetails) {
            // See similar code in VehicleTest for an explanation
            $errors = fn(array $errors) => array_merge($errors, $isEdit ? [] : ['#details_isAdult']);

            return [
                new FormTestCase([
                    'details[name]' => '',
                    'details[number]' => '',
                ], $errors(['#details_name', '#details_number'])),
                new FormTestCase([
                    'details[name]' => $name,
                    'details[number]' => '-5',
                ], $errors(['#details_number'])),
                new FormTestCase([
                    'details[name]' => $name,
                    'details[number]' => '0',
                ], $errors(['#details_number'])),
                new FormTestCase([
                    'details[name]' => $name,
                    'details[number]' => $capiNumber,
                    'details[isAdult]' => $isAdult ? 'true' : 'false',
                ]),
            ];
        } else {
            return [
                new FormTestCase([]),
            ];
        }
    }

    protected function getSecondStepTestCases(bool $overwriteDetails, string $email): array
    {
        if ($overwriteDetails) {
            return [
                new FormTestCase([
                    'user_identifier[user][username]' => '',
//                    'user_identifier[user][consent]' => '',
                ], ['#user_identifier_user_username']),
                new FormTestCase([
                    'user_identifier[user][username]' => 'Silly',
//                    'user_identifier[user][consent]' => '',
                ], [
                    '#user_identifier_user_username',
//                    '#user_identifier_user_consent'
                ]),
                new FormTestCase([
                    'user_identifier[user][username]' => 'Not@Email',
//                    'user_identifier[user][consent]' => '',
                ], [
                    '#user_identifier_user_username',
//                    '#user_identifier_user_consent'
                ]),
//                new FormTestCase([
//                    'user_identifier[user][username]' => $email,
//                    'user_identifier[user][consent]' => '',
//                ], [
//                    '#user_identifier_user_consent'
//                ]),
                new FormTestCase([
                    'user_identifier[user][username]' => $email,
//                    'user_identifier[user][consent]' => '1',
                ]),
            ];
        } else {
            return [
                new FormTestCase([]),
            ];
        }
    }

    protected function fetchDiaryKeeperAndStoreId(array $criteria, string $contextKey): callable
    {
        return function (Context $context)
        use ($criteria, $contextKey) {
            $diaryKeeper = $context->getEntityManager()->getRepository(DiaryKeeper::class)->findOneBy($criteria);
            $context->set($contextKey, $diaryKeeper->getId());
        };
    }

    protected function diaryKeeperDatabaseCheck(
        string $name,
        string $capiNumber,
        bool   $isAdult,
        ?string $email,
        array $proxyKeys = []
    ): callable
    {
        return function (Context $context)
        use ($name, $capiNumber, $isAdult, $email, $proxyKeys) {
            $diaryKeeperId = $context->get('diary-keeper');
            $diaryKeeperRepository = $context->getEntityManager()->getRepository(DiaryKeeper::class);
            $proxies = array_map(fn($key) => $diaryKeeperRepository->findOneBy(['id' => $context->get($key)]), $proxyKeys);

            $diaryKeeper = $context->getEntityManager()->getRepository(DiaryKeeper::class)->findOneBy(['id' => $diaryKeeperId]);

            $testCase = $context->getTestCase();
            $testCase->assertEquals($name, $diaryKeeper->getName());
            $testCase->assertEquals(intval($capiNumber), $diaryKeeper->getNumber());
            $testCase->assertEquals($isAdult, $diaryKeeper->getIsAdult());

            $userIdentifier = $diaryKeeper->getUser()->getUserIdentifier();
            if ($email !== null) {
                $testCase->assertEquals($email, $userIdentifier);
            } else {
                $testCase->assertStringStartsWith('no-login:', $userIdentifier);
            }

            $hasProxies = !empty($proxyKeys);
            $testCase->assertEquals($hasProxies, $diaryKeeper->hasProxies());

            if ($hasProxies) {
                foreach($proxies as $proxy) {
                    $this->assertTrue($diaryKeeper->isProxiedBy($proxy));
                }
            }
        };
    }

    protected function clickDiaryKeeperEditLink(string $name, ?string $rowValue = null): callable
    {
        return function (Context $context)
        use ($name, $rowValue) {
            $testCase = $context->getTestCase();
            // Click the tab header...
            $testCase->clickLinkContaining($name, 0, "//*[@id='diary-keepers']/ul/li/");

            // and then the relevant edit link
            $testCase->clickLinkContaining('Change', 0,
                "//*[@id='diary-keepers']/div//dd[@class='govuk-summary-list__value']" .
                "[contains(text(), '" . ($rowValue ?? $name) . "')]/following-sibling::dd/");
        };
    }
}