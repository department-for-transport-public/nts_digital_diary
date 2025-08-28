<?php

namespace App\Serializer;

use App\Entity\AreaPeriod;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AreaSampleImportDenormalizer implements DenormalizerInterface
{

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return ($context['sampleImport'] ?? false);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): array
    {
        $areas = [];
        foreach ($data as $datum) {
            $areas[] = (new AreaPeriod())
                ->setArea($datum['Area'])
                ->setYear($datum['Year'])
                ->setMonth($datum['Month']);
        }
        return $areas;
    }
}