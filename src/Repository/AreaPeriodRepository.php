<?php

namespace App\Repository;

use App\Entity\AreaPeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AreaPeriod|null find($id, $lockMode = null, $lockVersion = null)
 * @method AreaPeriod|null findOneBy(array $criteria, array $orderBy = null)
 * @method AreaPeriod[]    findAll()
 * @method AreaPeriod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AreaPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AreaPeriod::class);
    }

    /**
     * @param iterable<AreaPeriod> $areaPeriods
     * @return array
     */
    public static function groupAreaPeriodsByYear(iterable $areaPeriods): array
    {
        $areaPeriodsByYear = [];

        foreach($areaPeriods as $areaPeriod) {
            $year = $areaPeriod->getYear();
            if (!isset($areaPeriodsByYear[$year])) {
                $areaPeriodsByYear[$year] = [];
            }

            $areaPeriodsByYear[$year][] = $areaPeriod;
        }

        krsort($areaPeriodsByYear);

        foreach($areaPeriodsByYear as $year => $areas) {
            // We decided to order by AreaID ASC, as that would encourage interviewers to unsubscribe from areas
            // that they had finished with.
            usort($areaPeriodsByYear[$year], fn(AreaPeriod $a, AreaPeriod $b) => $a->getArea() <=> $b->getArea());
        }

        return $areaPeriodsByYear;
    }

    public function findForUniqueConstraint(array $criteria): array
    {
        $qb = $this->createQueryBuilder('ap');

        foreach($criteria as $field => $criterion) {
            $qb
                ->andWhere("ap.{$field} = :{$field}")
                ->setParameter($field, $criterion);
        }

        return $qb->getQuery()->execute();
    }
}
