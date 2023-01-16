<?php

namespace App\Serializer\ApiPlatform;

use App\Entity\Interviewer;
use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

class InterviewerDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return ($context['resource_class'] ?? null) === Interviewer::class;
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): Interviewer
    {
        return (new Interviewer())
            ->setName($data['name'] ?? null)
            ->setSerialId($data['serialId'] ?? null)
            ->setUser((new User())->setUserIdentifier($data['email'] ?? null));
    }
}