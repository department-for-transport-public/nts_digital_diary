<?php

namespace App\Serializer;

use App\Entity\Metrics\MetricsLog;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SurveyParadataExportNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    public const string CONTEXT_KEY = 'survey-paradata-export';

    /**
     * @param MetricsLog $object
     *
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): ?array
    {
        return [
            'id' => $object->getId(),
            'createdAt' => $this->normalizer->normalize($object->getCreatedAt(), $format, $context),
            'userSerial' => $object->getUserSerial(),
            'diarySerial' => $object->getDiarySerial(),
            'event' => $object->getEvent(),
            'metadata_json' => json_encode($object->getMetadata()),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $format === 'csv'
            && $data instanceof MetricsLog
            && isset($context[self::CONTEXT_KEY])
            && $context[self::CONTEXT_KEY];
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            MetricsLog::class => true,
        ];
    }
}
