<?php

namespace App\Tests\Entity\Journey;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class StageValidationTest extends TestCase
{
    private function createContext(array &$capturedParams): ExecutionContextInterface
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnCallback(function (string $key, $value) use ($builder, &$capturedParams) {
            $capturedParams[$key] = $value;
            return $builder;
        });
        $builder->method('atPath')->willReturnSelf();
        $builder->method('addViolation')->willReturn(null);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->method('buildViolation')
            ->with('common.string.max-length')
            ->willReturn($builder);

        return $context;
    }

    public function testValidateMethodOtherTooLongIncludesValueLength(): void
    {
        $longString = str_repeat('a', 256);

        // Use a real Method instance — Method has a static method() that conflicts with PHPUnit mocking
        $method = Method::method(1, null, 'other-private', 'private', 'private');

        $stage = new Stage();
        $stage->setMethod($method);
        $stage->setMethodOther($longString);

        $capturedParams = [];
        $context = $this->createContext($capturedParams);

        $stage->validateMethodOther($context);

        $this->assertArrayHasKey('{{ limit }}', $capturedParams);
        $this->assertEquals(255, $capturedParams['{{ limit }}']);
        $this->assertArrayHasKey('{{ value_length }}', $capturedParams);
        $this->assertEquals(256, $capturedParams['{{ value_length }}']);
    }

    public function testValidateVehicleAndVehicleOtherTooLongIncludesValueLength(): void
    {
        $longString = str_repeat('b', 300);

        $stage = new Stage();
        $stage->setVehicle(null);
        $stage->setVehicleOther($longString);

        $capturedParams = [];
        $context = $this->createContext($capturedParams);

        $stage->validateVehicleAndVehicleOther($context);

        $this->assertArrayHasKey('{{ limit }}', $capturedParams);
        $this->assertEquals(255, $capturedParams['{{ limit }}']);
        $this->assertArrayHasKey('{{ value_length }}', $capturedParams);
        $this->assertEquals(300, $capturedParams['{{ value_length }}']);
    }
}
