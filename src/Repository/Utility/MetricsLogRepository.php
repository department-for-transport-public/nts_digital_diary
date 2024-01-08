<?php

namespace App\Repository\Utility;

use App\Entity\Utility\MetricsLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MetricsLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method MetricsLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method MetricsLog[]    findAll()
 * @method MetricsLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MetricsLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetricsLog::class);
    }

    // /**
    //  * @return MetricsLog[] Returns an array of MetricsLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MetricsLog
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
