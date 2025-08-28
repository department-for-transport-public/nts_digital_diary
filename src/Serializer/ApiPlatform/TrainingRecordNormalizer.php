<?php

namespace App\Serializer\ApiPlatform;

use App\Entity\Interviewer;
use App\Entity\InterviewerTrainingRecord;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TrainingRecordNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context['resource_class'] ?? null) === Interviewer::class
            && ($data instanceof InterviewerTrainingRecord);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            InterviewerTrainingRecord::class => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    /**
     * @param $object InterviewerTrainingRecord
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            'moduleNumber' => $object->getModuleNumber(),
            'moduleName' => $this->translator->trans("training.module.title.{$object->getModuleName()}", [], 'interviewer'),
            'latestId' => $object->getId(),
            'state' => $object->getState(),
            'created' => $object->getCreatedAt()?->getTimestamp(),
            'started' => $object->getStartedAt()?->getTimestamp(),
            'completed' => $object->getCompletedAt()?->getTimestamp(),
        ];
    }
}