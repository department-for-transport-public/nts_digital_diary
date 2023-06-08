<?php

namespace App\DataFixtures;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\DataFixtures\Definition\StageDefinition;
use App\Entity\CostOrNil;
use App\Entity\DiaryDay;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Repository\Journey\MethodRepository;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FixtureHelper
{
    public static function createJourney(JourneyDefinition $definition, DiaryDay $day): Journey
    {
        $journey = (new Journey())
            ->setStartTime(new \DateTime($definition->getStartTime()))
            ->setEndTime(new \DateTime($definition->getEndTime()))
            ->setDiaryDay($day)
            ->setPurpose($definition->getPurpose());

        self::setHomeAndLocation($journey, 'Start', $definition);
        self::setHomeAndLocation($journey, 'End', $definition);

        return $journey;
    }

    protected static function setHomeAndLocation(Journey $journey, string $prefix, JourneyDefinition $definition): void {
        $accessor = PropertyAccess::createPropertyAccessor();
        $location = $accessor->getValue($definition, "{$prefix}Location");

        $accessor->setValue($journey, "Is{$prefix}Home", $location === 'Home');
        $accessor->setValue($journey, "{$prefix}Location", $location === 'Home' ? null : $location);
    }

    public static function createStage(StageDefinition $definition, MethodRepository $methodRepository, array $vehicles): Stage {
        $method = $methodRepository->findOneBy(['descriptionTranslationKey' => $definition->getMethod()]);

        if (!$method) {
            throw new \RuntimeException("Invalid method: {$definition->getMethod()}");
        }

        $stage = (new Stage())
            ->setNumber($definition->getNumber())
            ->setMethod($method)
            ->setMethodOther($definition->getMethodOther())
            ->setDistanceTravelled($definition->getDistance())
            ->setTravelTime($definition->getTravelTime())
            ->setAdultCount($definition->getAdultCount())
            ->setChildCount($definition->getChildCount());

        switch($method->getType() ?? null) {
            case Method::TYPE_PUBLIC:
                if (!$definition instanceof PublicStageDefinition) throw new \RuntimeException('invalid type');

                $stage
                    ->setTicketType($definition->getTicketType())
                    ->setTicketCost((new CostOrNil())->setCost($definition->getTicketCost()))
                    ->setBoardingCount($definition->getBoardingCount());
                break;

            case Method::TYPE_PRIVATE:
                if (!$definition instanceof PrivateStageDefinition) throw new \RuntimeException('invalid type');
                $vehicle = $vehicles[$definition->getVehicle()] ?? null;

                if ($vehicle) {
                    $stage
                        ->setVehicle($vehicle)
                        ->setVehicleOther(null);
                } else {
                    $stage
                        ->setVehicle(null)
                        ->setVehicleOther($definition->getVehicle());
                }

                $stage
                    ->setIsDriver($definition->getIsDriver())
                    ->setParkingCost((new CostOrNil())->setCost($definition->getParkingCost()));
        }

        return $stage;
    }
}