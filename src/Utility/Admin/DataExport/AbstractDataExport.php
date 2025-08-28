<?php

namespace App\Utility\Admin\DataExport;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractDataExport
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected SerializerInterface $serializer,
    ) {}

    abstract public function getExportDataResponse(): Response;

    protected function getFileDownloadResponse(string $csvData, string $description, string $filenamePrefix): Response
    {
        return new Response($csvData, Response::HTTP_OK, $this->getResponseHeaders($description, $filenamePrefix));
    }

    protected function getResponseHeaders(string $description, string $filenamePrefix): array
    {
        return [
            'Content-Type' => 'text/csv',
            'Content-Description' => $description,
            'Content-Disposition' => 'attachment; filename="'. $filenamePrefix . '-'.(new DateTime())->format('Ymd-His').'.csv"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
        ];
    }
}