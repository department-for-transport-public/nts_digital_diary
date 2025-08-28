<?php

namespace App\Serializer\ApiPlatform;

use App\Entity\Interviewer;
use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class InterviewerDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return ($context['resource_class'] ?? null) === Interviewer::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): Interviewer
    {
        return (new Interviewer())
            ->setSerialId($data['serialId'] ?? null)
            ->setName($data['name'] ?? null)
            ->setUser((new User())->setUserIdentifier($data['email'] ?? null));
    }
}