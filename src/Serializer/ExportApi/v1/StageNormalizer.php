<?php

namespace App\Serializer\ExportApi\v1;

use App\Entity\Journey\Stage;
use App\Serializer\ExportApi\Utils;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class StageNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return
            ($context['apiVersion'] ?? 0) === 1
            && $data instanceof Stage;
    }

    /**
     * @param Stage $object
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            '#' => $object->getNumber(),
            'methodCode' => $object->getMethod()->getCode(),
            'methodOther' => $object->getMethodOther(),
            'distance' => $object->getDistanceTravelled()->getValue(),
            'distanceUnit' => $object->getDistanceTravelled()->getUnit(),
            'childCount' => $object->getChildCount() ?? 0,
            'adultCount' => $object->getAdultCount() ?? 0,
            'travelTime' => $object->getTravelTime(),
            'boardingCount' => $object->getBoardingCount(),
            'ticketCost' => Utils::formatFloat($object->getTicketCost()->getCost()),
            'ticketType' => $object->getTicketType(),
            'isDriver' => $object->getIsDriver(),
            'parkingCost' => Utils::formatFloat($object->getParkingCost()?->getCost()),
            'vehicle' => $object->getVehicle() ? $object->getVehicle()->getFriendlyName() : $object->getVehicleOther(),
            'vehicleCapiNumber' => $object->getVehicle() ?->getCapiNumber(),

            '_history' => $this->normalizer->normalize(Utils::getHistoryForObject($context, $object), $format, [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true]),
        ];
    }

}