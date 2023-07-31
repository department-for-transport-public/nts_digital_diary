<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use Brick\Math\BigDecimal;

class StagePropertyChangeLogTest extends AbstractPropertyChangeLogTest
{
    public function dataPropertyChangeLog(): array
    {
        $privateStage = 'journey:1/stage:1';
        $publicStage = 'journey:2/stage:1';

        $getDecimal = fn(?string $value) => fn() => $value === null ?: BigDecimal::of($value);
        $getMethod = fn(int $id) => fn() => $this->entityManager->getRepository(Method::class)->find($id);
        $getReference = fn(string $name) => fn() => $this->referenceRepository->getReference($name);

        $getTests = fn(string $userDesc, string $userRef) => [
            // $userRef, $refName, $refClass, $propertyPath, $value, $changeLogIsExpected, $expectedLoggedPath = null, $expectedLoggedValue = null
            "$userDesc: Stage => adultCount              (non-change)"      => [$userRef, $privateStage, Stage::class, 'adultCount', 1, false],
            "$userDesc: Stage => adultCount              (change)"          => [$userRef, $privateStage, Stage::class, 'adultCount', 2, true],
            "$userDesc: Stage => boardingCount           (non-change)"      => [$userRef, $publicStage, Stage::class, 'boardingCount', 1, false],
            "$userDesc: Stage => boardingCount           (change)"          => [$userRef, $publicStage, Stage::class, 'boardingCount', 2, true],
            "$userDesc: Stage => childCount              (non-change)"      => [$userRef, $privateStage, Stage::class, 'childCount', 0, false],
            "$userDesc: Stage => childCount              (change)"          => [$userRef, $privateStage, Stage::class, 'childCount', 1, true],
            "$userDesc: Stage => distanceTravelled value (non-change #1)"   => [$userRef, $privateStage, Stage::class, 'distanceTravelled.value', $getDecimal('30'), false],
            "$userDesc: Stage => distanceTravelled value (non-change #2)"   => [$userRef, $privateStage, Stage::class, 'distanceTravelled.value', $getDecimal('30.0'), false],
            "$userDesc: Stage => distanceTravelled value (non-change #3)"   => [$userRef, $privateStage, Stage::class, 'distanceTravelled.value', $getDecimal('30.00'), false],
            "$userDesc: Stage => distanceTravelled value (change)"          => [$userRef, $privateStage, Stage::class, 'distanceTravelled.value', $getDecimal('30.01'), true, 'distance'],
            "$userDesc: Stage => distanceTravelled unit  (non-change)"      => [$userRef, $privateStage, Stage::class, 'distanceTravelled.unit', 'miles', false],
            "$userDesc: Stage => distanceTravelled unit  (change)"          => [$userRef, $privateStage, Stage::class, 'distanceTravelled.unit', 'meters', true, 'distanceUnit'],
            "$userDesc: Stage => isDriver                (non-change)"      => [$userRef, $privateStage, Stage::class, 'isDriver', true, false],
            "$userDesc: Stage => isDriver                (change)"          => [$userRef, $privateStage, Stage::class, 'isDriver', false, true],
            "$userDesc: Stage => method                  (non-tracked)"     => [$userRef, $privateStage, Stage::class, 'method', $getMethod(1), false],
            "$userDesc: Stage => methodOther             (non-tracked)"     => [$userRef, $privateStage, Stage::class, 'methodOther', 'submarine', false],
            "$userDesc: Stage => number                  (non-change)"      => [$userRef, $privateStage, Stage::class, 'number', 1, false, '#'],
            "$userDesc: Stage => number                  (change)"          => [$userRef, $privateStage, Stage::class, 'number', 2, true, '#'],
            "$userDesc: Stage => parkingCost             (non-change)"      => [$userRef, $privateStage, Stage::class, 'parkingCost.cost', $getDecimal('0.00'), false],
            "$userDesc: Stage => parkingCost             (change)"          => [$userRef, $privateStage, Stage::class, 'parkingCost.cost', $getDecimal('1.00'), true, 'parkingCost', '1.00'],
            "$userDesc: Stage => ticketCost              (non-change)"      => [$userRef, $publicStage, Stage::class, 'ticketCost.cost', $getDecimal('3.50'), false],
            "$userDesc: Stage => ticketCost              (change)"          => [$userRef, $publicStage, Stage::class, 'ticketCost.cost', $getDecimal('2.00'), true, 'ticketCost', '2.00'],
            "$userDesc: Stage => ticketType              (non-change)"      => [$userRef, $publicStage, Stage::class, 'ticketType', 'standard-ticket', false],
            "$userDesc: Stage => ticketType              (change)"          => [$userRef, $publicStage, Stage::class, 'ticketType', 'super-ticket', true],
            "$userDesc: Stage => travelTime              (non-change)"      => [$userRef, $privateStage, Stage::class, 'travelTime', 30, false],
            "$userDesc: Stage => travelTime              (change)"          => [$userRef, $privateStage, Stage::class, 'travelTime', 31, true],
            "$userDesc: Stage => vehicle                 (non-change)"      => [$userRef, $privateStage, Stage::class, 'vehicle', null, false],
            "$userDesc: Stage => vehicle                 (change)"          => [$userRef, $privateStage, Stage::class, 'vehicle', $getReference('vehicle:1'), true, 'vehicle', 'A-Team van'],
            "$userDesc: Stage => vehicleOther            (non-change)"      => [$userRef, $privateStage, Stage::class, 'vehicleOther', null, false],
            "$userDesc: Stage => vehicleOther            (change)"          => [$userRef, $privateStage, Stage::class, 'vehicleOther', 'Red combine harvester', true, 'vehicle'],
        ];

        return array_merge(
            $getTests('Diary Keeper', 'user:diary-keeper:adult'),
            $getTests('Interviewer ', 'user:interviewer'),
        );
    }
}