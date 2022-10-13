<?php


namespace App\Serializer\ExportApi\v1;


use App\Entity\DiaryDay;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class DiaryDayNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    const DATE_FORMAT_KEY = 'diary-day-date-format';

    use NormalizerAwareTrait;

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return
            ($context['apiVersion'] ?? 0) === 1
            && $data instanceof DiaryDay;
    }

    /**
     * @param DiaryDay $object
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $localContext = $context;
        $localContext[DateTimeNormalizer::FORMAT_KEY] = $context[self::DATE_FORMAT_KEY] ?? 'Y-m-d';

        return [
            'dayNumber' => $object->getNumber(),
            'date' => $this->normalizer->normalize($object->getDate(), $format, $localContext),
            'diaryKeeperNotes' => $object->getDiaryKeeperNotes(),
            'interviewerNotes' => $object->getInterviewerNotes(),
            'journeys' => $this->normalizer->normalize($object->getJourneys(), $format, $context),
        ];
    }
}