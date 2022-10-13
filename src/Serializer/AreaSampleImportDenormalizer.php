<?php

namespace App\Serializer;

use App\Entity\AreaPeriod;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

class AreaSampleImportDenormalizer implements ContextAwareDenormalizerInterface
{

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return ($context['sampleImport'] ?? false);
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