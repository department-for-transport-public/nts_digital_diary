<?php

namespace App\Repository\Utility;

use App\Entity\Utility\MaintenanceLock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class MaintenanceLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceLock::class);
    }

    public function isLocked()
    {
        try {
            $queryResult =  $this->createQueryBuilder('m')
                ->select('m.whitelistedIps')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
            return json_decode($queryResult, true);
        } catch (NoResultException $e) {
            return false;
        }
    }
}