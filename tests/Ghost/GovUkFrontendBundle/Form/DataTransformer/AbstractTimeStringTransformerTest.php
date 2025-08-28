<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\DataTransformer;

use App\Form\OldGhostBundle\DataTransformer\TimeStringTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

Abstract class AbstractTimeStringTransformerTest extends TestCase
{
    abstract function getMode(): string;
    abstract function transformData(): array;
    abstract function reverseTransformData(): array;

    /**
     * @dataProvider transformData
     */
    public function testTransform(mixed $dateValue, ?array $expectedArrayValue, bool $expectedToThrow=false): void
    {
        if ($expectedToThrow) {
            $this->expectException(TransformationFailedException::class);
        }

        $transformer = new TimeStringTransformer($this->getMode());
        self::assertSame($expectedArrayValue, $transformer->transform($dateValue));
    }

    /**
     * @dataProvider reverseTransformData
     */
    public function testReverseTransform(mixed $arrayValue, ?\DateTime $expectedDateValue, bool $expectedToThrow=false): void
    {
        if ($expectedToThrow) {
            $this->expectException(TransformationFailedException::class);
        }

        $transformer = new TimeStringTransformer($this->getMode());
        self::assertEquals($expectedDateValue, $transformer->reverseTransform($arrayValue));
    }
}
