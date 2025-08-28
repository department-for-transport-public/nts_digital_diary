<?php

namespace App\Serializer\ApiPlatform;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Entity\Interviewer;
use App\Repository\InterviewerTrainingRecordRepository;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class InterviewerNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(private readonly InterviewerTrainingRecordRepository $trainingRecordRepository)
    {
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context['resource_class'] ?? null) === Interviewer::class
            && ($data instanceof Interviewer);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Interviewer::class => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    /**
     * @param $object Interviewer
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $interviewer = [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'serialId' => $object->getSerialId(),
            'email' => $object->getUser()->getUserIdentifier(),
        ];

        if ($context['operation'] instanceof Get || $context['operation'] instanceof Post) {
            $interviewer['area_periods'] = $this->normalizer->normalize(
                $object->getAreaPeriods(),
                $format,
                array_merge($context, [EntityAsIdNormalizer::CONTEXT_KEY => true])
            );
            $interviewer['training_record'] = $this->normalizer->normalize(
                $this->trainingRecordRepository->findLatestForInterviewer($object),
                $format,
                $context,
            );
        }

        return $interviewer;
    }
}