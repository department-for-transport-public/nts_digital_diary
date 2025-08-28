<?php

namespace App\Serializer\ApiPlatform;

use ApiPlatform\Metadata\Get;
use App\Entity\AreaPeriod;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AreaPeriodNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context['resource_class'] ?? null) === AreaPeriod::class
            && ($data instanceof AreaPeriod);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            AreaPeriod::class => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    /**
     * @param $object AreaPeriod
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $area = [
            'id' => $object->getId(),
            'area' => $object->getArea(),
            'year' => $object->getYear(),
            'month' => $object->getMonth(),
        ];

        if ($context['operation'] instanceof Get) {
            $area['interviewers'] = $this->normalizer->normalize(
                $object->getInterviewers(),
                $format,
                array_merge($context, [EntityAsIdNormalizer::CONTEXT_KEY => true])
            );
            $area['onboarding_codes'] = $this->normalizer->normalize($object->getOtpUsers(), $format, $context);
        }

        return $area;
    }
}