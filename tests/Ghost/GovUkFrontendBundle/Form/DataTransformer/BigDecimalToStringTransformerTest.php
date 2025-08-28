<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Brick\Math\BigDecimal;
use Ghost\GovUkFrontendBundle\Form\DataTransformer\BigDecimalToStringTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BigDecimalToStringTransformerTest extends TestCase
{
    public function transformData(): array
    {
        return [
            [2, true, null, null],

            // Exception - not a bigDecimal
            [2, true, "2.5", null, true],
            [2, true, 35, null, true],

            // Trim trailing decimals
            [2, true, BigDecimal::of(2), '2'],
            [2, true, BigDecimal::of(2.3), '2.3'],
            [2, true, BigDecimal::of(2.35), '2.35'],

            // Do not trim trailing decimals
            [2, false, BigDecimal::of(2), '2.00'],
            [2, false, BigDecimal::of(2.3), '2.30'],
            [2, false, BigDecimal::of(2.35), '2.35'],

            // A number too big for the desired scale
            [2, true, BigDecimal::of(2.355), '2.355']
        ];
    }

    /**
     * @dataProvider transformData
     */
    public function testTransform(int $scale, bool $trimTrailingDecimalZeroes, mixed $bigDecimalValue, ?string $expectedStringValue, bool $expectedToThrow=false): void
    {
        if ($expectedToThrow) {
            $this->expectException(TransformationFailedException::class);
        }

        $transformer = new BigDecimalToStringTransformer($scale, 'Error!', [], $trimTrailingDecimalZeroes);
        self::assertSame($expectedStringValue, $transformer->transform($bigDecimalValue));
    }

    public function reverseTransformData(): array
    {
        return [
            // Empty values
            [2, true, null, null],
            [2, true, '', null],
            [2, false, null, null],
            [2, false, '', null],

            // Exception - cannot be parsed as a number, or is explicitly disallowed
            [2, true, 'banana', null, true],
            [2, true, 'e12', null, true],
            [2, true, '2e5', null, true],
            [2, true, '2+6', null, true],
            [2, true, '2^6', null, true],

            // Actually copes with other types
            [2, true, 35, BigDecimal::of(35)->toScale(2)],
            [2, true, BigDecimal::zero(), BigDecimal::zero()->toScale(2)],

            // No number preceeding decimal point
            [2, true, '.1', BigDecimal::of(0.1)->toScale(2)],

            [2, true, '2', BigDecimal::of(2)->toScale(2)],
            [2, true, '2.3', BigDecimal::of(2.3)->toScale(2)],
            [2, true, '2.35', BigDecimal::of(2.35)->toScale(2)],

            [2, false, '2', BigDecimal::of(2)->toScale(2)],
            [2, false, '2.3', BigDecimal::of(2.3)->toScale(2)],
            [2, false, '2.35', BigDecimal::of(2.35)->toScale(2)],

            // A number exceeding the desired scale
            [2, true, '2.355', BigDecimal::of(2.355)],
        ];
    }

    /**
     * @dataProvider reverseTransformData
     */
    public function testReverseTransform(int $scale, bool $trimTrailingDecimalZeroes, mixed $stringValue, ?BigDecimal $expectedBigDecimalValue, bool $expectedToThrow=false): void
    {
        if ($expectedToThrow) {
            $this->expectException(TransformationFailedException::class);
        }

        $transformer = new BigDecimalToStringTransformer($scale, 'Error!', [], $trimTrailingDecimalZeroes);
        self::assertEquals($expectedBigDecimalValue, $transformer->reverseTransform($stringValue));
    }
}
