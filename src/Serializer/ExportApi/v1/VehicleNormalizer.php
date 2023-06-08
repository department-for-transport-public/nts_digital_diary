<?php

namespace App\Serializer\ExportApi\v1;

use App\Entity\Vehicle;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class VehicleNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return
            ($context['apiVersion'] ?? 0) === 1
            && $data instanceof Vehicle;
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