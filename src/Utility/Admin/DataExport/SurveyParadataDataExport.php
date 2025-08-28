<?php

namespace App\Utility\Admin\DataExport;

use App\Entity\Utility\MetricsLog;
use App\Serializer\SurveyParadataExportNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class SurveyParadataDataExport extends AbstractDataExport
{

    public function getExportDataResponse(): Response
    {
        return new StreamedResponse(function ()
            {
                @ob_end_clean();
                @ini_set("zlib.output_compression", 1);
                $isFirst = true;
                foreach ($this->getParadata() as $row) {
                    echo $this->serializer->serialize($row, 'csv', [
                        DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
                        CsvEncoder::NO_HEADERS_KEY => !$isFirst,
                        SurveyParadataExportNormalizer::CONTEXT_KEY => true,
                    ]);
                    $this->em->detach($row);
                    $isFirst = false;
                    flush();
                }
            },
            Response::HTTP_OK,
            $this->getResponseHeaders('NTS survey paradata', 'nts-paradata')
        );
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