<?php

namespace App\Tests\Serializer\ChangeSet;

use App\Entity\Journey\Method;
use App\Serializer\ChangeSet\StageChangeSetNormalizer;
use PHPUnit\Framework\TestCase;

class StageNormalizerTest extends TestCase
{
    public function dataFieldChanges(): array
    {
        $method = new Method();

        return [
            // Test unrelated fields
            [
                ['modifiedAt' => [0,1], 'nodifiedAt' => [0,1]],
                [],
            ],

            // Test removals
            [
                ['adultCount' => [0,1], 'nodifiedAt' => [0,1]],
                ['adultCount' => [0,1]],
            ],
            [
                ['boardingCount' => [0,1], 'nodifiedAt' => [0,1]],
                ['boardingCount' => [0,1]],
            ],
            [
                ['childCount' => [0,1], 'nodifiedAt' => [0,1]],
                ['childCount' => [0,1]],
            ],
            [
                ['isDriver' => [false, true], 'nodifiedAt' => [0,1]],
                ['isDriver' => [false, true]],
            ],
//            [
//                ['method' => [$method, null], 'methodOther' => [null, 'Banana'], 'nodifiedAt' => [0,1]],
//                ['method' => [$method, null], 'methodOther' => [null, 'Banana']],
//            ],
            [
                ['parkingCost' => [null, 345], 'nodifiedAt' => [0,1]],
                ['parkingCost' => [null, 345]],
            ],
            [
                ['travelTime' => [null, 35], 'nodifiedAt' => [0,1]],
                ['travelTime' => [null, 35]],
            ],
            [
                ['ticketCost' => [null, 850], 'nodifiedAt' => [0,1]],
                ['ticketCost' => [null, 850]],
            ],
            [
                ['ticketType' => [null, 'Banana'], 'nodifiedAt' => [0,1]],
                ['ticketType' => [null, 'Banana']],
            ],

            // Test vehicle combination
            [
                ['nodifiedAt' => [0,1], 'vehicle' => [null, 'Blue Micra'], 'vehicleOther' => [null, null]],
                ['vehicle' => [null, 'Blue Micra']],
            ],
            [
                ['nodifiedAt' => [0,1], 'vehicle' => [null, null], 'vehicleOther' => [null, 'Blue Micra']],
                ['vehicle' => [null, 'Blue Micra']],
            ],
            [
                ['nodifiedAt' => [0,1], 'vehicle' => ['Green Porche', null], 'vehicleOther' => [null, 'Blue Micra']],
                ['vehicle' => ['Green Porche', 'Blue Micra']],
            ],
            [
                ['nodifiedAt' => [0,1], 'vehicle' => [null, 'Blue Micra'], 'vehicleOther' => ['Green Porche', null]],
                ['vehicle' => ['Green Porche', 'Blue Micra']],
            ],

            // Test renames
            [
                ['zodifiedAt' => [0,1], 'distanceTravelled.value' => [null, '300.23'], 'distanceTravelled.unit' => [null, 'miles']],
                ['distanceTravelled' => [null, '300.23'], 'distanceTravelledUnit' => [null, 'miles']],
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