<?php

namespace App\Serializer\ExportApi\v1;

use App\Entity\Household;
use App\Features;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class HouseholdNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    const TRAVEL_WEEK_START_DATE_FORMAT_KEY = 'travel-week-start-date-format';

    use NormalizerAwareTrait;

    public function __construct(
        protected Features $features
    ) {}

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return
            ($context['apiVersion'] ?? 0) === 1
            && $data instanceof Household;
    }

    /**
     * @param Household $object
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $dateNormalizationContext = [DateTimeNormalizer::FORMAT_KEY => $context[self::TRAVEL_WEEK_START_DATE_FORMAT_KEY] ?? 'Y-m-d'];

        $head = [
            'area' => $object->getAreaPeriod()->getArea(),
            'address' => $object->getAddressNumber(),
            'household' => $object->getHouseholdNumber(),
            'travelWeekStartDate' => $this->normalizer->normalize($object->getDiaryWeekStartDate(), $format, $dateNormalizationContext),
        ];

        $mid = [];
        if ($this->features->isEnabled(Features::MILOMETER)) {
            $mid['vehicles'] = $this->normalizer->normalize($object->getVehicles(), $format, $context);
        }

        $tail = [
            'diaryKeepers' => $this->normalizer->normalize($object->getDiaryKeepers(), $format, $context),
            'submittedBy' => $object->getSubmittedBy(),
            'submittedAt' => $this->normalizer->normalize($object->getSubmittedAt(), $format, $context),
        ];

        return array_merge($head, $mid, $tail);
    }
}