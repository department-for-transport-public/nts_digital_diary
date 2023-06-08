<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Entity\OtpUser;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\DataFixtures\TestSpecific\DiaryKeeperForVehicleOnboardingFixtures;
use App\Tests\Functional\Wizard\Action;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;

class VehicleTest extends AbstractOtpTest
{
    const USER_IDENTIFIER = '2345678901';

    protected function generateTest(bool $doEdit, bool $overwriteDetails): array
    {
        $firstVehicle = [
            'vehicleName' => 'Red ID3',
            'methodId' => 2,
            'capiNumber' => 1,
            'primaryDriverId' => $this->getDiaryKeeperByReference('diary-keeper:not-onboarded:1')->getId(),
        ];

        $secondVehicle = [
            'vehicleName' => 'Green ID Buzz',
            'methodId' => 3,
            'capiNumber' => 2,
            'primaryDriverId' => $this->getDiaryKeeperByReference('diary-keeper:not-onboarded:2')->getId(),
        ];

        $getStepTestCases = function (bool $isEdit, bool $overwriteDetails, string $vehicleName, string $methodId, string $capiNumber, ?string $primaryDriverId): array {
            if ($overwriteDetails) {
                return [
                    new FormTestCase([
                            'vehicle[friendlyName]' => '',
                            'vehicle[capiNumber]' => '',
                        ], array_merge(
                        // Some explanation:
                        // a) If we're adding, a radio button isn't selected, so it'll generate an error
                        // b) If we're editing, a radio button is already selected, so it won't error
                        // c) It's impossible to turn off a radio button if it's already one (i.e. you can't
                        //    deselect all of the radios). Trying to pass '' / null through submitForm
                        //    ends up with a radio button still selected.
                        //
                        //    (Which makes sense since there's no way, as a user, to deselect a radio button in a browser)
                        ['#vehicle_friendlyName', '#vehicle_capiNumber'],
                            $isEdit ? [] : ['#vehicle_method', '#vehicle_primaryDriver'],
                        )
                    ),
                    new FormTestCase([
                        'vehicle[friendlyName]' => $vehicleName,
                        'vehicle[capiNumber]' => $capiNumber,
                        'vehicle[primaryDriver]' => $primaryDriverId,
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
            $getStepTestCases(false, true, ...$firstVehicle));

        $tests[] = new PathTestAction('/onboarding');

        $tests[] = new CallbackAction($this->detailsDatabaseCheck(self::USER_IDENTIFIER, ...$firstVehicle));

        if ($doEdit) {
            $tests[] = new Action\CallbackAction(function (Context $context) {
                $testCase = $context->getTestCase();
                // $testCase->getSummaryListData("//*[@id='household-vehicles']/div");

                // Click the edit link...
                $testCase->clickLinkContaining('Change', 0, "//*[@id='household-vehicles']/div/div/div/dd/");
            });

            $tests[] = new FormTestAction(
                '#^\/onboarding\/vehicle\/[A-Z0-9]+\/edit$#',
                'vehicle_button_group_save',
                $getStepTestCases(true, $overwriteDetails, ...$secondVehicle),
                [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

            $expectedVehicle = $overwriteDetails ? $secondVehicle : $firstVehicle;

            $tests[] = new CallbackAction($this->detailsDatabaseCheck(self::USER_IDENTIFIER, ...$expectedVehicle));

//            $tests[] = new Action\CallbackAction(function (Client $client, AbstractWizardTest $test) {
//                $data = $test->getSummaryListData("//*[@id='household-vehicles']/div");
//            });
        }

        return $tests;
    }

    protected function detailsDatabaseCheck(string $userIdentifier, string $vehicleName, int $methodId, int $capiNumber, ?string $primaryDriverId): callable
    {
        return function (Context $context)
        use ($userIdentifier, $vehicleName, $methodId, $capiNumber, $primaryDriverId) {
            $otpUser = $context->getEntityManager()->getRepository(OtpUser::class)->findOneBy(['userIdentifier' => $userIdentifier]);

            $household = $otpUser->getHousehold();
            $vehicles = $household->getVehicles();

            $testCase = $context->getTestCase();
            $testCase->assertCount(1, $vehicles);

            $vehicle = $vehicles[0];

            $testCase->assertEquals($vehicleName, $vehicle->getFriendlyName());
            $testCase->assertEquals($methodId, $vehicle->getMethod()->getId());
            $testCase->assertEquals($capiNumber, $vehicle->getCapiNumber());
            $testCase->assertEquals($primaryDriverId, $vehicle->getPrimaryDriver()->getId());
        };
    }

    public function wizardData(): array
    {
        return [
            'Onboarding: add vehicle' => [[false, true]],
            'Onboarding: edit vehicle' => [[true, true]],
            'Onboarding: edit vehicle w/details overwrite' => [[true, true]],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testOnboardingVehicleWizard(array $wizardData)
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class, DiaryKeeperForVehicleOnboardingFixtures::class]);
        $identifier = self::USER_IDENTIFIER; // OTP user where address #, household #, and diary week start date already entered
        $this->loginOtpUser($identifier, $this->passcodeGenerator->getPasswordForUserIdentifier($identifier));

        $this->clickLinkContaining('Add a vehicle');

        $this->doWizardTest($this->generateTest(...$wizardData));
    }

    protected function getDiaryKeeperByReference(string $fixtureReference): DiaryKeeper
    {
        /** @var DiaryKeeper */
        return $this->getFixtureByReference($fixtureReference);
    }
}