<?php

namespace App\Repository\Metrics;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Metrics\MetricsLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function findByDiaryKeeper(DiaryKeeper $diaryKeeper): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.diarySerial = :diarySerial')
            ->setParameter('diarySerial', $diaryKeeper->getSerialNumber(spacesBetweenParts: false))
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Base query builder for a household's metrics, scoped by diary serial. Used by the
     * admin metrics list page (the paginator wraps this, so it must not call getQuery()).
     */
    public function createHouseholdMetricsQueryBuilder(Household $household): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $this->applyHouseholdScope($queryBuilder, $household);

        return $queryBuilder;
    }

    /**
     * The distinct event names logged for a household, for populating the event filter dropdown.
     *
     * @return string[]
     */
    public function findDistinctEventsForHousehold(Household $household): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->select('DISTINCT m.event')
            ->orderBy('m.event', 'ASC');
        $this->applyHouseholdScope($queryBuilder, $household);

        return array_column($queryBuilder->getQuery()->getScalarResult(), 'event');
    }

    /**
     * Constrain a metrics query to a single household. MetricsLog has no FK to Household; it
     * links via the diarySerial string, which is either household-level ("A01/01/1") or
     * diary-keeper-level ("A01/01/1/2"). The trailing "/" boundary in the LIKE prevents
     * household 1 from also matching household 10.
     */
    private function applyHouseholdScope(QueryBuilder $queryBuilder, Household $household): void
    {
        $serial = $household->getSerialNumber(padAddressPart: true, spacesBetweenParts: false);

        $queryBuilder
            ->andWhere('m.diarySerial = :serial OR m.diarySerial LIKE :serialPrefix')
            ->setParameter('serial', $serial)
            ->setParameter('serialPrefix', $serial . '/%');
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
