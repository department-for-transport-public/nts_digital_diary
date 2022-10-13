<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\CostTransformer;
use PHPUnit\Framework\TestCase;

class MoneyTypeTest extends TestCase
{
    public function transformData(): array
    {
        return [
            [null, ''],
            [0, '0.00'],
            [700, '7.00'],
            [2, '0.02'],
            [502, '5.02'],
            [50, '0.50'],
            [220, '2.20'],
            [72, '0.72'],
            [123, '1.23'],
        ];
    }

    /**
     * @dataProvider transformData
     */
    public function testTransformer($intValue, $stringValue): void
    {
        $transformer = new CostTransformer();
        self::assertSame($stringValue, $transformer->transform($intValue));
        self::assertSame($intValue, $transformer->reverseTransform($stringValue));
    }
}
