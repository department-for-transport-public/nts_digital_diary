<?php

namespace App\Tests\Form\Type\TravelDiary\StageWizard;

use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Form\TravelDiary\StageWizard\DetailsType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class DetailsTypeTest extends AbstractKernelTypeTest
{
    private static array $validData = [
        'distanceTravelled' => ['value' => '25', 'unit' => 'miles'],
        'travelTime' => '50',
        'companions' => ['adultCount' => '1', 'childCount' => '1'],
    ];

    private function createStage(bool $isAdult): Stage
    {
        $stage = new Stage();
        $journey = (new Journey())->addStage($stage);
        $dk = (new DiaryKeeper())->setIsAdult($isAdult);
        (new DiaryDay())->setNumber(1)->addJourney($journey)->setDiaryKeeper($dk);
        return $stage;
    }

    public function dataForm(): array
    {
        return [
            'Valid: complete data' => [self::$validData, true, true],

            // Travel time
            'Invalid: travel time zero' => [
                array_merge(self::$validData, ['travelTime' => '0']),
                false, true,
            ],
            'Invalid: travel time exceeds max' => [
                array_merge(self::$validData, ['travelTime' => '10001']),
                false, true,
            ],
            'Valid: travel time at max' => [
                array_merge(self::$validData, ['travelTime' => '10000']),
                true, true,
            ],

            // Distance
            'Invalid: distance zero with non-zero travel time' => [
                array_merge(self::$validData, ['distanceTravelled' => ['value' => '0', 'unit' => 'miles']]),
                false, true,
            ],

            // Companion counts
            'Invalid: adult count negative' => [
                array_merge(self::$validData, ['companions' => ['adultCount' => '-1', 'childCount' => '1']]),
                false, true,
            ],
            'Invalid: child count negative' => [
                array_merge(self::$validData, ['companions' => ['adultCount' => '1', 'childCount' => '-1']]),
                false, true,
            ],
            'Invalid: adult count exceeds max' => [
                array_merge(self::$validData, ['companions' => ['adultCount' => '1001', 'childCount' => '1000']]),
                false, true,
            ],
            'Invalid: child count exceeds max' => [
                array_merge(self::$validData, ['companions' => ['adultCount' => '1000', 'childCount' => '1001']]),
                false, true,
            ],
            'Invalid: adult diary keeper with zero adults' => [
                array_merge(self::$validData, ['companions' => ['adultCount' => '0', 'childCount' => '0']]),
                false, true,
            ],
            'Invalid: child diary keeper with zero children' => [
                array_merge(self::$validData, ['companions' => ['adultCount' => '0', 'childCount' => '0']]),
                false, false,
            ],
            'Valid: child diary keeper with zero adults but non-zero children' => [
                array_merge(self::$validData, ['companions' => ['adultCount' => '0', 'childCount' => '1']]),
                true, false,
            ],
            'Valid: adult diary keeper with zero children but non-zero adults' => [
                array_merge(self::$validData, ['companions' => ['adultCount' => '1', 'childCount' => '0']]),
                true, true,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid, bool $isAdult): void
    {
        $stage = $this->createStage($isAdult);

        $form = $this->formFactory->create(DetailsType::class, $stage, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
