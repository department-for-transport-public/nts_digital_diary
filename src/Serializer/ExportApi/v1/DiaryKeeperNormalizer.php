<?php

namespace App\Serializer\ExportApi\v1;

use App\Entity\DiaryKeeper;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class DiaryKeeperNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    const CHANGELOG_KEY = 'change-log';
    const DATE_FORMAT_KEY = 'diary-keeper-date-format';

    use NormalizerAwareTrait;

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return
            ($context['apiVersion'] ?? 0) === 1
            && $data instanceof DiaryKeeper;
    }

    /**
     * @param DiaryKeeper $object
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $dayContext = $context;
        $dayContext[self::CHANGELOG_KEY] = $this->normalizer->normalize($object, $format, [
            'history' => true,
            'originalContext' => $context,
        ]);

        return [
            'person' => $object->getNumber(),
            'name' => $object->getName(),
            'isAdult' => $object->getIsAdult(),
            'mediaType' => $object->getMediaType(),
            'state' => $object->getDiaryState(),
            'hasUsedPracticeDay' => $object->getPracticeDay()->getJourneys()?->count() > 0,
            'approvalChecklistVerifiedBy' => $object->getApprovalChecklistVerifiedBy(),
            'approvalChecklistVerifiedAt' => $this->normalizer->normalize($object->getApprovalChecklistVerifiedAt(), $format, $context),
            'days' => $this->normalizer->normalize($object->getDiaryDays()->getValues(), $format, $dayContext),
        ];
    }
}