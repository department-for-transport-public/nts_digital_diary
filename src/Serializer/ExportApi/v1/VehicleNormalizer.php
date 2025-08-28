<?php

namespace App\Serializer\ExportApi\v1;

use App\Entity\Vehicle;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class VehicleNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return
            ($context['apiVersion'] ?? 0) === 1
            && $data instanceof Vehicle;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Vehicle::class => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    /**
     * @param Vehicle $object
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            'capiNumber' => $object->getCapiNumber(),
            'name' => $object->getFriendlyName(),
            'odometerUnit' => $object->getOdometerUnit(),
            'weekStartOdometerReading' => $object->getWeekStartOdometerReading(),
            'weekEndOdometerReading' => $object->getWeekEndOdometerReading(),
        ];
    }

}