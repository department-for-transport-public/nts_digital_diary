<?php

namespace App\Utility\Admin\DataExport;

use App\Entity\SatisfactionSurvey;
use App\Serializer\SatisfactionSurveyExportNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class SatisfactionSurveyDataExport extends AbstractDataExport
{
    public function getExportData(): string
    {
        return $this->serializer->serialize($this->getSurveyData(), 'csv', [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
            SatisfactionSurveyExportNormalizer::CONTEXT_KEY => true,
        ]);
    }

    protected function getSurveyData(): array
    {
        return $this->em->getRepository(SatisfactionSurvey::class)->findAll();
    }
}