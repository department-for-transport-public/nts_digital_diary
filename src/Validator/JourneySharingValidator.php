<?php

namespace App\Validator;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class JourneySharingValidator
{
    public static function validateStage(Stage $stage, ExecutionContextInterface $context): void
    {
        self::revalidatePropertyWithParameters($context, $stage, 'isDriver');
        self::revalidatePropertyWithParameters($context, $stage, 'parkingCost');
        self::revalidatePropertyWithParameters($context, $stage, 'ticketType');
        self::revalidatePropertyWithParameters($context, $stage, 'ticketCost');
    }

    public static function validateJourney(Journey $journey, ExecutionContextInterface $context): void
    {
        self::revalidatePropertyWithParameters($context, $journey, 'purpose');
    }

    public static function revalidatePropertyWithParameters(ExecutionContextInterface $context, Stage|Journey $entity, string $property, ?string $overrideMessage = null, array $additionalParameters = []): void
    {
        $errors = $context->getValidator()
            ->validateProperty($entity, $property, "{$context->getGroup()}.entry");

        foreach($errors as $error) {
            $context
                ->buildViolation(
                    $overrideMessage ?? $error->getMessageTemplate(),
                    array_merge($error->getParameters(), self::getDefaultTranslationParameters($entity), $additionalParameters)
                )
                ->atPath($error->getPropertyPath())
                ->addViolation();
        }
    }

    private static function getDefaultTranslationParameters(Stage|Journey $entity): array
    {
        $journey = $entity instanceof Journey ? $entity : $entity->getJourney();
        return [
            'name' => $journey->getDiaryDay()->getDiaryKeeper()->getName(),
        ];
    }
}