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

    abstract public function getExportData(): string|iterable;
}