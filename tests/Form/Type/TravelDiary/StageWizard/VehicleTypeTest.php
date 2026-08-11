<?php

namespace App\Tests\Form\Type\TravelDiary\StageWizard;

use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\Vehicle;
use App\Form\TravelDiary\StageWizard\VehicleDataMapper;
use App\Form\TravelDiary\StageWizard\VehicleType;
use App\Repository\DiaryKeeperRepository;
use App\Repository\VehicleRepository;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class VehicleTypeTest extends AbstractKernelTypeTest
{
    private Stage $stage;

    public function setUp(): void
    {
        parent::setUp();

        $method = Method::method(2, 4, 'car', 'private', 'private');
        $vehicle = (new Vehicle())->setFriendlyName('Red Tesla')->setMethod($method);
        $household = (new Household())->addVehicle($vehicle);
        $dk = (new DiaryKeeper())->setIsAdult(true)->setHousehold($household);

        $this->stage = (new Stage())->setMethod($method);
        $journey = (new Journey())->addStage($this->stage);
        (new DiaryDay())->setNumber(1)->addJourney($journey)->setDiaryKeeper($dk);

        // Inject VehicleType with mocked repositories
        $dkRepo = $this->createMock(DiaryKeeperRepository::class);
        $dkRepo->method('findVehiclesNamedByDiaryKeeper')->willReturn([]);
        $vehicleRepo = $this->createMock(VehicleRepository::class);

        $this->queueFormTypeForPreload(new VehicleType($dkRepo, $vehicleRepo));
        $this->preloadFormTypes();
    }

    public function dataForm(): array
    {
        return [
            'Valid: select household vehicle' => [
                ['vehicle' => 'Red Tesla'],
                true,
            ],
            'Valid: select other and provide name' => [
                ['vehicle' => VehicleDataMapper::OTHER_KEY, 'vehicleOther' => 'Blue Golf'],
                true,
            ],

            // Required
            'Invalid: empty submission' => [[], false],

            // Other validation
            'Invalid: select other without name' => [
                ['vehicle' => VehicleDataMapper::OTHER_KEY, 'vehicleOther' => ''],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $form = $this->formFactory->create(VehicleType::class, $this->stage, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
