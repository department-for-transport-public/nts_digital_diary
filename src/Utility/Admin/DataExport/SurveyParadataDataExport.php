<?php

namespace App\Utility\Admin\DataExport;

use App\Entity\Metrics\MetricsLog;
use App\Serializer\SurveyParadataExportNormalizer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class SurveyParadataDataExport extends AbstractDataExport
{
    public function getExportData(): iterable
    {
        $isFirst = true;
        foreach ($this->getParadata() as $row) {
            yield $this->serializer->serialize($row, 'csv', [
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
                CsvEncoder::NO_HEADERS_KEY => !$isFirst,
                SurveyParadataExportNormalizer::CONTEXT_KEY => true,
            ]);
            $this->em->detach($row);
            $isFirst = false;
        }
    }

    protected function getParadata(): iterable
    {
        return $this->em->getRepository(MetricsLog::class)
            ->createQueryBuilder('ml')
            ->getQuery()
            ->toIterable()
            ;
    }
}