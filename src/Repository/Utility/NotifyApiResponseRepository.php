<?php

namespace App\Repository\Utility;

use App\Entity\Utility\NotifyApiResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotifyApiResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotifyApiResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotifyApiResponse[]    findAll()
 * @method NotifyApiResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotifyApiResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotifyApiResponse::class);
    }
}
