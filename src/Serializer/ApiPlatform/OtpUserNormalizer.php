<?php

namespace App\Serializer\ApiPlatform;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Security\OneTimePassword\PasscodeGenerator;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class OtpUserNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private PasscodeGenerator $passcodeGenerator;

    public function __construct(PasscodeGenerator $passcodeGenerator)
    {
        $this->passcodeGenerator = $passcodeGenerator;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context['resource_class'] ?? null) === AreaPeriod::class
            && ($context['operation_type'] ?? null) === 'item'
            && ($data instanceof OtpUser);
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