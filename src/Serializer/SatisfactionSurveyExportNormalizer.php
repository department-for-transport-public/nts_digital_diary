<?php

namespace App\Serializer;

use App\Entity\SatisfactionSurvey;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SatisfactionSurveyExportNormalizer implements NormalizerInterface
{
    public const string CONTEXT_KEY = 'satisfaction-survey-export';

    /**
     * @param SatisfactionSurvey $object
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): ?array
    {
        return [
            'id' => $object->getId(),
            'easeRating' => $object->getEaseRating(),
            'burdenRating' => $object->getBurdenRating(),
            'burdenReasons' => join(',', $object->getBurdenReason()),
            'burdenReasonOther' => $object->getBurdenReasonOther(),
            'typesOfDevices' => join(',', $object->getTypeOfDevices()),
            'typesOfDevicesOther' => $object->getTypeOfDevicesOther(),
            'howOftenEntriesAdded' => $object->getHowOftenEntriesAdded(),
            'writtenNoteKept' => $object->getWrittenNoteKept(),
            'preferredMethod' => $object->getPreferredMethod(),
            'preferredMethodOther' => $object->getPreferredMethodOther(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $format === 'csv'
            && $data instanceof SatisfactionSurvey
            && isset($context[self::CONTEXT_KEY])
            && $context[self::CONTEXT_KEY];
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SatisfactionSurvey::class => true,
        ];
    }
}
