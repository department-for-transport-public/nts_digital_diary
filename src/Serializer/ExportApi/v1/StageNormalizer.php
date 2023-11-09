<?php

namespace App\Serializer\ExportApi\v1;

use App\Entity\Journey\Stage;
use App\Serializer\ExportApi\Utils;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\RoundingNecessaryException;
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
     * @throws RoundingNecessaryException
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        // N.B. SQLite does not store decimals with fixed places, so we need to additionally scale here to make
        //      output consistent across both site usage and tests.
        $decimalToString = fn(?BigDecimal $decimal) => $decimal === null ?
            null :
            strval($decimal->toScale(2));

        $method = $object->getMethod();

        return [
            '#' => $object->getNumber(),
            'methodCode' => $method->getCode(),
            'methodOther' => $method->isOtherRequired()
                ? $this->normalizer->normalize($object->getMethodForDisplay())
                : null,
            'distance' => $decimalToString($object->getDistanceTravelled()->getValue()),
            'distanceUnit' => $object->getDistanceTravelled()->getUnit(),
            'childCount' => $object->getChildCount() ?? 0,
            'adultCount' => $object->getAdultCount() ?? 0,
            'travelTime' => $object->getTravelTime(),
            'boardingCount' => $object->getBoardingCount(),
            'ticketCost' => $object->getTicketCost()?->encodeToSingleValue(),
            'ticketType' => $object->getTicketType(),
            'isDriver' => $object->getIsDriver(),
            'parkingCost' => $object->getParkingCost()?->encodeToSingleValue(),
            'vehicle' => $object->getVehicle() ? $object->getVehicle()->getFriendlyName() : $object->getVehicleOther(),
            'vehicleCapiNumber' => $object->getVehicle() ?->getCapiNumber(),

            '_history' => $this->normalizer->normalize(Utils::getHistoryForObject($context, $object), $format, [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true]),
        ];
    }

}