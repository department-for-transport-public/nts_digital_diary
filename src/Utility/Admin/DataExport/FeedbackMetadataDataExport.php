<?php

namespace App\Utility\Admin\DataExport;

use App\Entity\Feedback\Message;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Doctrine\ORM\Query\Expr;

class FeedbackMetadataDataExport extends AbstractDataExport
{
    public function getExportData(): string
    {
        return $this->serializer->serialize($this->getMetadata(), 'csv', [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s']);
    }

    protected function getMetadata(): array
    {
        return $this->em->getRepository(Message::class)
            ->createQueryBuilder('m')
            ->select('m.id as messageId', 'm.category', 'm.state', 'm.sent as received', 'm.assignedTo')
            ->addSelect("MAX(CASE WHEN n.note LIKE '%new to assigned%' THEN n.createdAt ELSE :null END) as assigned")
            ->addSelect("MAX(CASE WHEN n.note LIKE '%assigned to in-progress%' THEN n.createdAt ELSE :null END) as inProgress")
            ->addSelect("MAX(CASE WHEN n.note LIKE '%in-progress to closed%' THEN n.createdAt ELSE :null END) as closed")
            ->addSelect("MAX(CASE WHEN n.note LIKE '%to rejected%' THEN n.createdAt ELSE :null END) as rejected")
            ->leftJoin('m.notes', 'n', Expr\Join::WITH, "n.note like 'The message state changed from%'")
            ->groupBy('m.id', 'm.category', 'm.sent')
            ->orderBy('m.sent', 'ASC')
            ->setParameter('null', null)
            ->getQuery()
            ->getArrayResult();
    }
}