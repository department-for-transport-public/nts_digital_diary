<?php

namespace App\Validator;

use App\Entity\Journey\Stage;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StageSharingValidator
{
    public static function validateSharedDriversAndParkingCosts(Stage $sourceStage, ExecutionContextInterface $context)
    {
        $sharedStages = self::getSharedStagesAndDiaryKeepers($sourceStage);
        $driverFound = false;

        foreach($sharedStages as [$sharedStage, $diaryKeeper]) {
            if ($sharedStage->getIsDriver()) {
                if ($driverFound) {
                    $context
                        ->buildViolation('wizard.share-journey.is-driver.not-multiple', ['name' => $diaryKeeper->getName()])
                        ->atPath("isDriver-".$diaryKeeper->getId())
                        ->addViolation();
                    break;
                } else {
                    $driverFound = true;
                }
            }
        }

        $groups = ['wizard.share-journey.driver-and-parking'];
        foreach($sharedStages as $diaryKeeperId => [$sharedStage, $diaryKeeper]) {
            self::validateProperty($context, $sharedStage, 'isDriver', $diaryKeeperId, $groups, 'wizard.share-journey.is-driver.not-blank', ['name' => $diaryKeeper->getName()]);
            self::validateProperty($context, $sharedStage, 'parkingCost', $diaryKeeperId, $groups);
        }
    }

    public static function validateSharedTicketTypeAndCost(Stage $sourceStage, ExecutionContextInterface $context)
    {
        $sharedStages = self::getSharedStagesAndDiaryKeepers($sourceStage);
        $groups = ['wizard.share-journey.ticket-type-and-cost'];
        foreach($sharedStages as $diaryKeeperId => [$sharedStage, $diaryKeeper]) {
            self::validateProperty($context, $sharedStage, 'ticketType', $diaryKeeperId, $groups);
            self::validateProperty($context, $sharedStage, 'ticketCost', $diaryKeeperId, $groups);
        }
    }

    public static function validateProperty(ExecutionContextInterface $context, Stage $stage, string $property, string $diaryKeeperId, array $validationGroups, ?string $messageOverride = null, array $parametersOverride = []): void
    {
        $errors = $context->getValidator()
            ->validateProperty($stage, $property, $validationGroups);

        foreach($errors as $error) {
            $context
                ->buildViolation(
                    $messageOverride ?? $error->getMessageTemplate(),
                    array_merge($error->getParameters(), $parametersOverride)
                )
                ->atPath("{$property}-{$diaryKeeperId}")
                ->addViolation();
        }
    }

    /**
     * @return array | Stage[]
     */
    public static function getSharedStagesAndDiaryKeepers(Stage $stage): array
    {
        $sourceJourney = $stage->getJourney();
        $stageIndex = $sourceJourney->getStages()->indexOf($stage);

        $sharedStages = [];
        foreach($sourceJourney->getSharedTo() as $sharedJourney) {
            $diaryKeeper = $sharedJourney->getDiaryDay()->getDiaryKeeper();
            $sharedStages[$diaryKeeper->getId()] = [
                $sharedJourney->getStages()->get($stageIndex),
                $diaryKeeper,
            ];
        }

        return $sharedStages;
    }
}