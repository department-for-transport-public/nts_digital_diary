<?php

namespace App\Serializer\ChangeSet;

use App\Entity\Journey\Stage;
use App\Entity\Vehicle;

class StageChangeSetNormalizer extends AbstractChangeSetNormalizer
{
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return is_array($data) &&
            ($context[self::CHANGE_SET_ENTITY_KEY] ?? null) instanceof Stage;
    }

    public function normalize($object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        // Combine vehicle fields
        $updateVehicle = function($oldValue, $newValue) use (&$object) {
            $oldValue = ($oldValue instanceof Vehicle) ? $oldValue->getFriendlyName() : $oldValue;
            $newValue = ($newValue instanceof Vehicle) ? $newValue->getFriendlyName() : $newValue;

            $object['vehicle'] = [$oldValue, $newValue];
            unset($object['vehicleOther']);
        };

        $vehicle = $object['vehicle'] ?? null;
        $vehicleOther = $object['vehicleOther'] ?? null;

        if ($vehicle && $vehicle[1] !== null) {
            $updateVehicle($vehicleOther[0] ?? null, $vehicle[1]);
        } else if ($vehicleOther && $vehicleOther[1] !== null) {
            $updateVehicle($vehicle[0] ?? null, $vehicleOther[1]);
        }

        // Rename distanceTravelled fields
        $this->renameFields($object, [
            'number' => '#',
            'distanceTravelled.value' => 'distance',
            'distanceTravelled.unit' => 'distanceUnit',
            'parkingCost.cost' => 'parkingCost',
            'ticketCost.cost' => 'ticketCost',
            'parkingCost.hasCost' => 'parkingHasCost',
            'ticketCost.hasCost' => 'ticketHasCost',
        ]);

        $this->decimalToString($object, 'parkingCost');
        $this->decimalToString($object, 'ticketCost');

        $this->whitelistFields($object, [
            '#',
            'adultCount',
            'boardingCount',
            'childCount',
            'distance',
            'distanceUnit',
            'isDriver',
            'parkingCost',
            'parkingHasCost',
            'travelTime',
            'ticketCost',
            'ticketHasCost',
            'ticketType',
            'vehicle',
        ]);

        return $object;
    }
}