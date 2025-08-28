<?php

namespace App\Serializer\ApiPlatform;

use ApiPlatform\Metadata\Get;
use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Security\OneTimePassword\PasscodeGenerator;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class OtpUserNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(protected readonly PasscodeGenerator $passcodeGenerator)
    {
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context['resource_class'] ?? null) === AreaPeriod::class
            && ($context['operation'] ?? null) instanceof Get
            && $data instanceof OtpUser;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            OtpUser::class => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    /**
     * @param $object OtpUser
     */
    public function normalize($object, string $format = null, array $context = []): ?array
    {
        return [
            $object->getUserIdentifier(),
            $this->passcodeGenerator->getPasswordForUserIdentifier($object->getUserIdentifier()),
        ];
    }
}