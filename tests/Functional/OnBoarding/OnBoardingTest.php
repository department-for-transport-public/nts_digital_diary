<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\OtpUser;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Utility\TravelDiary\SerialHelper;

class OnBoardingTest extends AbstractOtpTest
{
    protected function generateTest(bool $isEdit, string $userIdentifier, bool $detailsOverwrite, int $number): array
    {
        $tests = [];

        if (!$isEdit) {
            $tests[] = new FormTestAction(
                '/onboarding/household/introduction',
                'form_button_group_continue',
                [
                    new FormTestCase([]),
                ]);
        }

        $tests[] = new FormTestAction(
            '/onboarding/household/details',
            'household_button_group_continue',
            $detailsOverwrite ? $this->getDetailsTestCases($number) : [new FormTestCase([])]);

        $tests[] = new PathTestAction('/onboarding');

        if ($detailsOverwrite) {
            $tests[] = new CallbackAction($this->detailsDatabaseCheck(
                $userIdentifier,
                12 + $number,
                2 + $number,
                '' . (2000 + $number) . '/' . (2 + $number) . '/' . (25 + $number),
                SerialHelper::getCheckLetter(111984, 12 + $number, 2 + $number),
            ));
        } else {
            // Same as the fixtures because we didn't edit
            $tests[] = new CallbackAction($this->detailsDatabaseCheck(
                $userIdentifier,
                6,
                1,
                '2021/11/15',
                SerialHelper::getCheckLetter(111984, 6, 1),
            ));
        }

        return [$tests, $isEdit, $userIdentifier];
    }

    /**
     * @return array
     */
    protected function getDetailsTestCases(int $adjustNumbersBy): array
    {
        $merge = fn(array $x) => array_merge([
            "household[addressNumber]" => '',
            "household[householdNumber]" => '',
            "household[checkLetter]" => '',
            "household[diaryWeekStartDate][day]" => '',
            "household[diaryWeekStartDate][month]" => '',
            "household[diaryWeekStartDate][year]" => '',
        ], $x);

        return [
            new FormTestCase($merge([]), [
                "#household_addressNumber",
                "#household_householdNumber",
                "#household_checkLetter",
                "#household_diaryWeekStartDate",
            ]),
            new FormTestCase($merge([
                "household[addressNumber]" => '-5',
                "household[householdNumber]" => '-5',
            ]), [
                "#household_diaryWeekStartDate",
                "#household_addressNumber",
                "#household_householdNumber",
                "#household_checkLetter",
            ]),
            new FormTestCase($merge([
                "household[addressNumber]" => '0',
                "household[householdNumber]" => '0',
            ]), [
                "#household_diaryWeekStartDate",
                "#household_addressNumber",
                "#household_householdNumber",
                "#household_checkLetter",
            ]),
            new FormTestCase($merge([
                "household[addressNumber]" => '99',
                "household[householdNumber]" => '99',
            ]), [
                "#household_diaryWeekStartDate",
                "#household_checkLetter",
            ]),
            new FormTestCase($merge([
                "household[addressNumber]" => '100',
                "household[householdNumber]" => '100',
            ]), [
                "#household_addressNumber",
                "#household_householdNumber",
                "#household_diaryWeekStartDate",
                "#household_checkLetter",
            ]),
            // checkLetter
            new FormTestCase($merge([
                "household[addressNumber]" => '10',
                "household[householdNumber]" => '1',
                "household[checkLetter]" => 'ABC'
            ]), [
                "#household_checkLetter",
                "#household_diaryWeekStartDate",
            ]),
            new FormTestCase($merge([
                "household[addressNumber]" => '10',
                "household[householdNumber]" => '1',
                "household[checkLetter]" => SerialHelper::getCheckLetter(111984, 10, 1),
            ]), [
                "#household_diaryWeekStartDate",
            ]),

            // N.B. Date field NOT extensively tested
            new FormTestCase([
                "household[addressNumber]" => strval(12 + $adjustNumbersBy),
                "household[householdNumber]" => strval(2 + $adjustNumbersBy),
                "household[checkLetter]" => SerialHelper::getCheckLetter(111984, 12 + $adjustNumbersBy, 2 + $adjustNumbersBy),
                "household[diaryWeekStartDate][day]" => strval(25 + $adjustNumbersBy),
                "household[diaryWeekStartDate][month]" => strval(2 + $adjustNumbersBy),
                "household[diaryWeekStartDate][year]" => strval(2000 + $adjustNumbersBy),
            ]),
        ];
    }

    protected function detailsDatabaseCheck(
        string $userIdentifier,
        int $expectedAddressNumber,
        int $expectedHouseholdNumber,
        string $expectedDiaryWeekStartDate,
        string $expectedCheckLetter
    ): callable
    {
        return function (Context $context)
        use ($userIdentifier, $expectedAddressNumber, $expectedHouseholdNumber, $expectedDiaryWeekStartDate, $expectedCheckLetter) {
            $otpUser = $context->getEntityManager()->getRepository(OtpUser::class)->findOneBy(['userIdentifier' => $userIdentifier]);

            $household = $otpUser->getHousehold();
            $testCase = $context->getTestCase();

            $testCase->assertEquals($expectedAddressNumber, $household->getAddressNumber());
            $testCase->assertEquals($expectedHouseholdNumber, $household->getHouseholdNumber());
            $testCase->assertDateEquals(\DateTime::createFromFormat('Y/m/d', $expectedDiaryWeekStartDate), $household->getDiaryWeekStartDate());
            $testCase->assertEquals($expectedCheckLetter, $household->getCheckLetter());
        };
    }

    public function wizardData(): array
    {
        return [
            'Onboarding' => $this->generateTest(false, '1234567890', true, 1),
            'Onboarding edit' => $this->generateTest(true, '2345678901', false, 2),
            'Onboarding edit w/details overwrite' => $this->generateTest(true, '2345678901', true, 3),
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testOnboardingWizard(array $wizardData, bool $isEdit, string $userIdentifier)
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);
        $this->loginOtpUser($userIdentifier, $this->passcodeGenerator->getPasswordForUserIdentifier($userIdentifier));

        if ($isEdit) {
            $this->clickLinkContaining('Change');
        }

        $this->doWizardTest($wizardData);
    }
}