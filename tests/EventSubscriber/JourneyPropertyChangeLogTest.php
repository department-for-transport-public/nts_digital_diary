<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Journey\Journey;

class JourneyPropertyChangeLogTest extends AbstractPropertyChangeLogTest
{
    public function dataPropertyChangeLog(): array
    {
        $journeyRef = 'journey:1';

        $getTests = fn(string $userDesc, string $userRef) => [
            // $userRef, $refName, $refClass, $propertyPath, $value, $changeLogIsExpected, $expectedLoggedPath = null, $expectedLoggedValue = null
            "$userDesc: Journey => startTime               (non-change)"      => [$userRef, $journeyRef, Journey::class, 'startTime', new \DateTime('2020-01-01 8:26'), false],
            "$userDesc: Journey => startTime               (change)"          => [$userRef, $journeyRef, Journey::class, 'startTime', new \DateTime('2020-01-01 8:27'), true, null, '08:27'],
            "$userDesc: Journey => endTime                 (non-change)"      => [$userRef, $journeyRef, Journey::class, 'endTime', new \DateTime('2020-01-01 8:56'), false],
            "$userDesc: Journey => endTime                 (change)"          => [$userRef, $journeyRef, Journey::class, 'endTime', new \DateTime('2020-01-01 8:57'), true, null, '08:57'],
            "$userDesc: Journey => isStartHome             (non-change)"      => [$userRef, $journeyRef, Journey::class, 'isStartHome', true, false],
            "$userDesc: Journey => isStartHome             (change)"          => [$userRef, $journeyRef, Journey::class, 'isStartHome', false, true, 'startIsHome'],
            "$userDesc: Journey => isEndHome               (non-change)"      => [$userRef, $journeyRef, Journey::class, 'isEndHome', false, false],
            "$userDesc: Journey => isEndHome               (change)"          => [$userRef, $journeyRef, Journey::class, 'isEndHome', true, true, 'endIsHome'],
            "$userDesc: Journey => startLocation           (non-change)"      => [$userRef, $journeyRef, Journey::class, 'startLocation', null, false],
            "$userDesc: Journey => startLocation           (change)"          => [$userRef, $journeyRef, Journey::class, 'startLocation', 'Banana', true],
            "$userDesc: Journey => endLocation             (non-change)"      => [$userRef, $journeyRef, Journey::class, 'endLocation', 'Wobble', false],
            "$userDesc: Journey => endLocation             (change)"          => [$userRef, $journeyRef, Journey::class, 'endLocation', null, true],
            "$userDesc: Journey => purpose                 (non-change)"      => [$userRef, $journeyRef, Journey::class, 'purpose', 'to-work', false],
            "$userDesc: Journey => purpose                 (change)"          => [$userRef, $journeyRef, Journey::class, 'purpose', Journey::TO_GO_HOME, true],
        ];

        return array_merge(
            $getTests('Diary Keeper', 'user:diary-keeper:adult'),
            $getTests('Interviewer ', 'user:interviewer'),
        );
    }
}