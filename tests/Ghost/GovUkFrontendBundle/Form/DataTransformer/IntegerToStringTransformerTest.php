<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\IntegerToStringTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class IntegerToStringTransformerTest extends TestCase
{
    public function transformData(): array
    {
        return [
            // Empty
            ['', ''],
            [null, ''],

            // Valid values
            ['2', '2'],
            ['-2', '-2'],
            ['12345', '12345'],
            ['-12345', '-12345'],

            // Invalid values
            ['1.2345', '', true],
            ['-1.2345', '', true],
            ['banana', '', true],
            ['1e10', '', true],
            ['1^2', '', true],
            ['1+3', '', true],
        ];
    }

    /**
     * @dataProvider transformData
     */
    public function testTransform(mixed $integerValue, string $expectedStringValue, bool $expectedToThrow=false): void
    {
        if ($expectedToThrow) {
            $this->expectException(TransformationFailedException::class);
        }

        $transformer = new IntegerToStringTransformer('Error!', []);
        self::assertSame($expectedStringValue, $transformer->transform($integerValue));
    }

    public function reverseTransformData(): array
    {
        return [
            // Empty values
            ['', null],
            [null, null],

            // Valid values
            ['2', 2],
            ['-2', -2],
            ['12345', 12345],
            ['-12345', -12345],

            // Invalid values
            ['1.2345', null, true],
            ['-1.2345', null, true],
            ['banana', null, true],
            ['1e10', null, true],
            ['1^2', null, true],
            ['1+3', null, true],
        ];
    }

    /**
     * @dataProvider reverseTransformData
     */
    public function testReverseTransform(mixed $stringValue, ?int $expectedIntValue, bool $expectedToThrow=false): void
    {
        if ($expectedToThrow) {
            $this->expectException(TransformationFailedException::class);
        }

        $transformer = new IntegerToStringTransformer('Error!', []);
        self::assertEquals($expectedIntValue, $transformer->reverseTransform($stringValue));
    }
}
