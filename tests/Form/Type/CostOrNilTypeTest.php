<?php

namespace App\Tests\Form\Type;

use App\Entity\Embeddable\CostOrNil;
use App\Form\CostOrNilType;
use Brick\Math\BigDecimal;
use Ghost\GovUkFrontendBundle\Form\Extension\ChoiceTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\ConditionalTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\FormTypeExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class CostOrNilTypeTest extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [
            new ChoiceTypeExtension(),
            new ConditionalTypeExtension(),
            new FormTypeExtension(),
        ];
    }

    public function dataDataToForm(): array
    {
        return [
            'hasCost: Null,  cost: Null'          => [null, null, null, null],
            'hasCost: True,  cost: Null'          => [true, null, 'true', null],
            'hasCost: True,  cost: Zero'          => [true, BigDecimal::of('0.00'), 'false', null],
            'hasCost: True,  cost: Positive cost' => [true, BigDecimal::of('1.30'), 'true', '1.30'],
            'hasCost: True,  cost: Negative cost' => [true, BigDecimal::of('-30.00'), 'true', '-30.00'],
            'hasCost: False, cost: Null'          => [false, null, 'false', null],
            'hasCost: False, cost: Zero'          => [false, BigDecimal::of('0.00'), 'false', null],
            'hasCost: False, cost: Positive cost' => [false, BigDecimal::of('1.30'), 'false', null],
            'hasCost: False, cost: Negative cost' => [false, BigDecimal::of('-30.00'), 'false', null],
        ];
    }

    /**
     * @dataProvider dataDataToForm
     */
    public function testDataToForm(?bool $hasCost, ?BigDecimal $inputCost, ?string $expectedHasCost, ?string $expectedCost): void
    {
        $data = (new CostOrNil())
            ->setHasCost($hasCost)
            ->setCost($inputCost);

        $form = $this->factory->create(CostOrNilType::class, $data, [
            'translation_prefix' => 'whatever',
        ]);

        $radio = $form->get(CostOrNilType::BOOLEAN_FIELD_NAME)->getViewData();
        $cost = $form->get(CostOrNilType::COST_FIELD_NAME)->getViewData();

        $this->assertEquals($expectedHasCost, $radio);
        $this->assertEquals($expectedCost, $cost);
    }

    public function dataFormToData(): array
    {
        return [
            'hasCost: Null,  cost: Null'           => [null, null, null, null],
            'hasCost: True,  cost: Null'           => ['true', null, true, null],
            'hasCost: True,  cost: Zero'           => ['true', '0.00', false, BigDecimal::zero()],
            'hasCost: True,  cost: Positive cost'  => ['true', '1.30', true, BigDecimal::of('1.30')],
            'hasCost: True,  cost: Negative cost'  => ['true', '-30.0', true, BigDecimal::of('-30')],
            'hasCost: False, cost: Null'           => ['false', null, false, BigDecimal::zero()],
            'hasCost: False, cost: Zero'           => ['false', '0.00', false, BigDecimal::zero()],
            'hasCost: False, cost: Positive cost'  => ['false', '1.30', false, BigDecimal::zero()],
            'hasCost: False, cost: Negative cost'  => ['false', '-30.0', false, BigDecimal::zero()],
        ];
    }

    /**
     * @dataProvider dataFormToData
     */
    public function testFormToData(?string $formHasCost, ?string $formCost, ?bool $expectedHasCost, ?BigDecimal $expectedCost): void
    {
        $form = $this->factory->create(CostOrNilType::class, null, [
            'translation_prefix' => 'whatever',
        ]);

        $form->submit([
            CostOrNilType::BOOLEAN_FIELD_NAME => $formHasCost,
            CostOrNilType::COST_FIELD_NAME => $formCost,
        ]);

        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();
        $this->assertInstanceOf(CostOrNil::class, $data);

        if ($expectedCost === null) {
            $this->assertNull($data->getCost());
        } else {
            if (!$data->getCost() instanceof BigDecimal) {
                // This case breaks isEqualTo, so needs to be performed separately
                $this->assertEquals($expectedCost, $data->getCost());
            } else {
                $this->assertTrue($expectedCost->isEqualTo($data->getCost()));
            }
        }

        $this->assertEquals($expectedHasCost, $data->getHasCost());
    }
}