<?php

namespace App\Tests\Serializer\ChangeSet;

use App\Serializer\ChangeSet\StageChangeSetNormalizer;
use PHPUnit\Framework\TestCase;

class StageNormalizerTest extends TestCase
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
                ['journey' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],
            [
                ['number' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],
            [
                ['distanceTravelled' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],

            // Test vehicle combination
            [
                ['nodifiedAt' => [0,1], 'vehicle' => [null, 'Blue Micra'], 'vehicleOther' => [null, null]],
                ['nodifiedAt' => [0,1], 'vehicle' => [null, 'Blue Micra']],
            ],
            [
                ['nodifiedAt' => [0,1], 'vehicle' => [null, null], 'vehicleOther' => [null, 'Blue Micra']],
                ['nodifiedAt' => [0,1], 'vehicle' => [null, 'Blue Micra']],
            ],
            [
                ['nodifiedAt' => [0,1], 'vehicle' => ['Green Porche', null], 'vehicleOther' => [null, 'Blue Micra']],
                ['nodifiedAt' => [0,1], 'vehicle' => ['Green Porche', 'Blue Micra']],
            ],
            [
                ['nodifiedAt' => [0,1], 'vehicle' => [null, 'Blue Micra'], 'vehicleOther' => ['Green Porche', null]],
                ['nodifiedAt' => [0,1], 'vehicle' => ['Green Porche', 'Blue Micra']],
            ],

            // Test renames
            [
                ['zodifiedAt' => [0,1], 'distanceTravelled.value' => [null, '300.23'], 'distanceTravelled.unit' => [null, 'miles']],
                ['zodifiedAt' => [0,1], 'distanceTravelled' => [null, '300.23'], 'distanceTravelledUnit' => [null, 'miles']],
            ],
        ];
    }

    /**
     * @dataProvider dataFieldChanges
     */
    public function testFieldChanges(array $input, array $expectedOutput): void
    {
        $normalizer = new StageChangeSetNormalizer();
        $this->assertEqualsCanonicalizing($expectedOutput, $normalizer->normalize($input));
    }
}