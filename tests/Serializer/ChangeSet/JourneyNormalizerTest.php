<?php

namespace App\Tests\Serializer\ChangeSet;

use App\Serializer\ChangeSet\JourneyChangeSetNormalizer;
use PHPUnit\Framework\TestCase;

class JourneyNormalizerTest extends TestCase
{
    public function dataFieldChanges(): array
    {
        return [
            // Test unrelated fields
            [
                ['modifiedAt' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],

            // Test removals
            [
                ['id' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],
            [
                ['diaryDay' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],
            [
                ['stages' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],
            [
                ['isPartial' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],
            [
                ['notifications' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],

            // Test start/end times
            [
                ['nodifiedAt' => [0,1], 'startTime' => [null, new \DateTime('2:03am')]],
                ['nodifiedAt' => [0,1], 'startTime' => [null, '02:03']],
            ],
            [
                ['nodifiedAt' => [0,1], 'startTime' => [null, new \DateTime('2:56pm')]],
                ['nodifiedAt' => [0,1], 'startTime' => [null, '14:56']],
            ],
            [
                ['nodifiedAt' => [0,1], 'endTime' => [null, new \DateTime('2:03am')]],
                ['nodifiedAt' => [0,1], 'endTime' => [null, '02:03']],
            ],
            [
                ['nodifiedAt' => [0,1], 'endTime' => [null, new \DateTime('2:56pm')]],
                ['nodifiedAt' => [0,1], 'endTime' => [null, '14:56']],
            ],
            [
                ['zodifiedAt' => [0,1], 'startTime' => [new \DateTime('2:03am'), new \DateTime('4:23am')]],
                ['zodifiedAt' => [0,1], 'startTime' => ['02:03', '04:23']],
            ],
            [
                ['zodifiedAt' => [0,1], 'startTime' => [new \DateTime('2:56pm'), new \DateTime('8:56pm')]],
                ['zodifiedAt' => [0,1], 'startTime' => ['14:56', '20:56']],
            ],
            [
                ['zodifiedAt' => [0,1], 'endTime' => [new \DateTime('2:03am'), new \DateTime('4:23am')]],
                ['zodifiedAt' => [0,1], 'endTime' => ['02:03', '04:23']],
            ],
            [
                ['zodifiedAt' => [0,1], 'endTime' => [new \DateTime('2:56pm'), new \DateTime('8:56pm')]],
                ['zodifiedAt' => [0,1], 'endTime' => ['14:56', '20:56']],
            ],

            // Test renames
            [
                ['zodifiedAt' => [0,1], 'isStartHome' => [true, false]],
                ['zodifiedAt' => [0,1], 'startIsHome' => [true, false]],
            ],
            [
                ['zodifiedAt' => [0,1], 'isEndHome' => [true, false]],
                ['zodifiedAt' => [0,1], 'endIsHome' => [true, false]],
            ],
        ];
    }

    /**
     * @dataProvider dataFieldChanges
     */
    public function testFieldChanges(array $input, array $expectedOutput): void
    {
        $normalizer = new JourneyChangeSetNormalizer();
        $this->assertEqualsCanonicalizing($expectedOutput, $normalizer->normalize($input));
    }
}