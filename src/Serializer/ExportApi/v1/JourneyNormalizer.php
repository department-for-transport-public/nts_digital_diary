<?php

namespace App\Serializer\ExportApi\v1;

use App\Entity\Journey\Journey;
use App\Serializer\ExportApi\Utils;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class JourneyNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context['apiVersion'] ?? 0) === 1
            && $data instanceof Journey;
    }

    /**
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $timeNormalizationContext = [DateTimeNormalizer::FORMAT_KEY => $context['TIME_FORMAT'] ?? 'H:i'];

        /** @var Journey $object */
        return [
            'startTime' => $this->normalizer->normalize($object->getStartTime(), $format, $timeNormalizationContext),
            'startLocation' => $object->getStartLocation(),
            'startIsHome' => $object->getIsStartHome(),
            'endTime' => $this->normalizer->normalize($object->getEndTime(), $format, $timeNormalizationContext),
            'endLocation' => $object->getEndLocation(),
            'endIsHome' => $object->getIsEndHome(),
            'stages' => $this->normalizer->normalize($object->getStages(), $format, $context),
            'purpose' => $object->getPurpose(),
            'purposeCode' => $object->getPurpose() === Journey::TO_GO_HOME ? 1 : null,

            '_history' => $this->normalizer->normalize(Utils::getHistoryForObject($context, $object), $format, [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true]),
        ];
    }
}