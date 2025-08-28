<?php

namespace App\Tests\Form\Type;

use Ghost\GovUkFrontendBundle\Form\Extension\ChoiceTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\ConditionalTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\FormTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\LabelIsPageHeadingExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\NotRequiredExtension;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\ExpressionValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Validation;

abstract class AbstractTypeTest extends TypeTestCase
{
    protected array $validators = [];

    #[\Override]
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory($this->getConstraintValidatorFactory())
            ->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    protected function getConstraintValidatorFactory(): ConstraintValidatorFactoryInterface
    {
        $validators = $this->getValidators();

        foreach($validators as $class => $validator) {
            $expectedClass = $validator::class;
            if ($class !== 'validator.expression' && $expectedClass !== $class) {
                $this->fail("getValidators() key/class mismatch [key: {$class} / expected: {$expectedClass}]");
            }
        }

        return (new class($validators) extends ConstraintValidatorFactory {
            public function __construct(array $validators)
            {
                parent::__construct();
                $this->validators = $validators;
            }
        });
    }

    protected function getValidators(): array
    {
        $expressionLanguage = new ExpressionLanguage();

        return [
            // For some reason the expression validator is keyed differently (see ConstraintValidatorFactory)
            'validator.expression' => new ExpressionValidator($expressionLanguage),
        ];
    }

    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new ChoiceTypeExtension(),
            new ConditionalTypeExtension(),
            new FormTypeExtension(),
            new LabelIsPageHeadingExtension(),
            new NotRequiredExtension(),
        ];
    }
}
