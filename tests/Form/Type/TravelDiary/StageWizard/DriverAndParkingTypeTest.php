<?php

namespace App\Tests\Form\Type\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use App\Form\TravelDiary\StageWizard\DriverAndParkingType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class DriverAndParkingTypeTest extends AbstractKernelTypeTest
{
    private static array $validData = [
        'isDriver' => 'true',
        'parkingCost' => ['hasCost' => 'true', 'cost' => '3.60'],
    ];

    public function dataForm(): array
    {
        return [
            'Valid: driver with parking cost' => [self::$validData, true],
            'Valid: passenger with no parking cost' => [
                ['isDriver' => 'false', 'parkingCost' => ['hasCost' => 'false']],
                true,
            ],

            // Required fields
            'Invalid: empty submission' => [[], false],
            'Invalid: missing parking cost' => [
                ['isDriver' => 'true'],
                false,
            ],

            // Parking cost validation
            'Invalid: parking cost non-numeric' => [
                ['isDriver' => 'true', 'parkingCost' => ['hasCost' => 'true', 'cost' => 'abc']],
                false,
            ],
            'Invalid: parking cost negative' => [
                ['isDriver' => 'true', 'parkingCost' => ['hasCost' => 'true', 'cost' => '-10']],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $stage = new Stage();

        $form = $this->formFactory->create(DriverAndParkingType::class, $stage, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
