<?php

namespace App\Serializer\ApiPlatform;

use ApiPlatform\Metadata\GetCollection;
use App\Entity\Household;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class HouseholdNormalizer implements NormalizerInterface
{
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context['resource_class'] ?? null) === Household::class
            && $data instanceof Household
            && ($context['operation'] ?? null) instanceof GetCollection;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Household::class => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    /**
     * @param $object Household
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            'area' => $object->getAreaPeriod()->getArea(),
            'address' => $object->getAddressNumber(),
            'household' => $object->getHouseholdNumber(),
            'travelWeekStartDate' => $object->getDiaryWeekStartDate()->format('Y-m-d'),
            'householdState' => $object->getState(),
            'diaryCount' => $object->getDiaryKeepers()->count(),
            'diaryStateCounts' => $object->getDiaryKeeperStateCounts(),
        ];
    }
}
