<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\OtpUser;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;

class VehicleTest extends AbstractOtpTest
{
    const USER_IDENTIFIER = '2345678901';

    protected function generateTest(bool $doEdit, bool $overwriteDetails): array
    {
        $firstVehicleName = 'Red ID3';
        $firstMethodId = '2';

        $secondVehicleName = 'Green ID Buzz';
        $secondMethodId = '3';

        $getStepTestCases = function (bool $isEdit, bool $overwriteDetails, string $vehicleName, string $methodId): array {
            if ($overwriteDetails) {
                return [
                    new FormTestCase([
                            'vehicle[friendlyName]' => '',
                        ], array_merge(
                        // Some explanation:
                        // a) If we're adding, a radio button isn't selected, so it'll generate an error
                        // b) If we're editing, a radio button is already selected, so it won't error
                        // c) It's impossible to turn off a radio button if it's already one (i.e. you can't
                        //    deselect all of the radios). Trying to pass '' / null through submitForm
                        //    ends up with a radio button still selected.
                        //
                        //    (Which makes sense since there's no way, as a user, to deselect a radio button in a browser)
                        ['#vehicle_friendlyName'],
                            $isEdit ? [] : ['#vehicle_method'],
                        )
                    ),
                    new FormTestCase([
                        'vehicle[friendlyName]' => $vehicleName,
                        'vehicle[method]' => $methodId,
                    ]),
                ];
            } else {
                return [
                    new FormTestCase([]),
                ];
            }
        };

        $tests = [];

        $tests[] = new FormTestAction(
            '/onboarding/vehicle/add',
            'vehicle_button_group_save',
            $getStepTestCases(false, true, $firstVehicleName, $firstMethodId));

        $tests[] = new PathTestAction('/onboarding');

        $tests[] = new CallbackAction($this->detailsDatabaseCheck(self::USER_IDENTIFIER, $firstVehicleName, $firstMethodId));

        if ($doEdit) {
            $tests[] = new Action\CallbackAction(function (Context $context) {
                $testCase = $context->getTestCase();
                // $testCase->getSummaryListData("//*[@id='household-vehicles']/div");

                // Click the edit link...
                $testCase->clickLinkContaining('Change', 0, "//*[@id='household-vehicles']/div/dd/ul/li/");
            });

            $tests[] = new FormTestAction(
                '#^\/onboarding\/vehicle\/[A-Z0-9]+\/edit$#',
                'vehicle_button_group_save',
                $getStepTestCases(true, $overwriteDetails, $secondVehicleName, $secondMethodId),
                [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

            $expectedVehicleName = $overwriteDetails ? $secondVehicleName : $firstVehicleName;
            $expectedMethodId = $overwriteDetails ? $secondMethodId : $firstMethodId;

            $tests[] = new CallbackAction($this->detailsDatabaseCheck(self::USER_IDENTIFIER, $expectedVehicleName, $expectedMethodId));

//            $tests[] = new Action\CallbackAction(function (Client $client, AbstractWizardTest $test) {
//                $data = $test->getSummaryListData("//*[@id='household-vehicles']/div");
//            });
        }

        return [$tests];
    }

    protected function detailsDatabaseCheck(
        string $userIdentifier,
        string $expectedVehicleName,
        string $expectedMethodId
    ): callable
    {
        return function (Context $context)
        use ($userIdentifier, $expectedVehicleName, $expectedMethodId) {
            $otpUser = $context->getEntityManager()->getRepository(OtpUser::class)->findOneBy(['userIdentifier' => $userIdentifier]);

            $household = $otpUser->getHousehold();
            $vehicles = $household->getVehicles();

            $testCase = $context->getTestCase();
            $testCase->assertCount(1, $vehicles);

            $vehicle = $vehicles[0];

            $testCase->assertEquals($expectedVehicleName, $vehicle->getFriendlyName());
            $testCase->assertEquals(intval($expectedMethodId), $vehicle->getMethod()->getId());
        };
    }

    public function wizardData(): array
    {
        return [
            'Onboarding: add vehicle' => $this->generateTest(false, true),
            'Onboarding: edit vehicle' => $this->generateTest(true, true),
            'Onboarding: edit vehicle w/details overwrite' => $this->generateTest(true, true),
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testOnboardingVehicleWizard(array $wizardData)
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);
        $identifier = self::USER_IDENTIFIER; // OTP user where address #, household #, and diary week start date already entered
        $this->loginOtpUser($identifier, $this->passcodeGenerator->getPasswordForUserIdentifier($identifier));

        $this->clickLinkContaining('Add a vehicle');
        $this->doWizardTest($wizardData);
    }
}