<?php

namespace App\Repository;

use App\Entity\SatisfactionSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SatisfactionSurvey|null find($id, $lockMode = null, $lockVersion = null)
 * @method SatisfactionSurvey|null findOneBy(array $criteria, array $orderBy = null)
 * @method SatisfactionSurvey[]    findAll()
 * @method SatisfactionSurvey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SatisfactionSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SatisfactionSurvey::class);
    }

    // /**
    //  * @return SatisfactionSurvey[] Returns an array of SatisfactionSurvey objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SatisfactionSurvey
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
