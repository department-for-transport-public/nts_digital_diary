<?php

namespace App\Repository;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Journey\Method;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Household|null find($id, $lockMode = null, $lockVersion = null)
 * @method Household|null findOneBy(array $criteria, array $orderBy = null)
 * @method Household[]    findAll()
 * @method Household[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HouseholdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Household::class);
    }

    public function findForExportByTimestamps(DateTime $startTime, DateTime $endTime)
    {
        // Preload related data in to the entity manager
        $this->createQueryBuilder('household')
            ->leftJoin('household.areaPeriod', 'areaPeriod')
            ->select('household, areaPeriod')
            ->where('household.submittedAt >= :startTime')
            ->andWhere('household.submittedAt < :endTime')
            // Types specified here because tests were failing due to sqlite and date comparison strangeness
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->getQuery()
            ->execute();
        $this->getEntityManager()->getRepository(Method::class)->findAll();
        // end preload

        return $this->addSelectJoinsAndOrdersForExport($this->createQueryBuilder('household'))
            ->where('household.submittedAt >= :startTime')
            ->andWhere('household.submittedAt < :endTime')
            ->andWhere('diaryKeeper.diaryState = :state')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->setParameter('state', DiaryKeeper::STATE_APPROVED)
            ->getQuery()
            ->execute();
    }

    public function findForExportByHouseholdSerials(array $householdSerials)
    {
        $em = $this->getEntityManager();

        // Preload related data in to the entity manager
        $qb = $this->createQueryBuilder('household');
        $qb
            ->leftJoin('household.areaPeriod', 'areaPeriod')
            ->select('household, areaPeriod')
            ->where($qb->expr()->in(
                $qb->expr()->concat('areaPeriod.area', $qb->expr()->literal('/'), 'household.addressNumber', $qb->expr()->literal('/'), 'household.householdNumber'),
                ':serials'
            ))
            ->andWhere('household.submittedAt IS NOT NULL')
            ->setParameter('serials', $householdSerials)
            ->getQuery()
            ->execute();
        $em->getRepository(Method::class)->findAll();
        // end preload

        $qb = $this->createQueryBuilder('household');
        return $this->addSelectJoinsAndOrdersForExport($qb)
            ->leftJoin('household.areaPeriod', 'areaPeriod')
            ->where($qb->expr()->in(
                $qb->expr()->concat('areaPeriod.area', $qb->expr()->literal('/'), 'household.addressNumber', $qb->expr()->literal('/'), 'household.householdNumber'),
                ':serials'
            ))
            ->andWhere('diaryKeeper.diaryState = :state')
            ->andWhere('household.submittedAt IS NOT NULL')
            ->setParameters([
                'serials' => $householdSerials,
                'state' => DiaryKeeper::STATE_APPROVED,
            ])
            ->getQuery()
            ->execute();
    }

    protected function addSelectJoinsAndOrdersForExport(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder->select('household, diaryKeeper, user, diaryDays, journeys, stages')
            ->leftJoin('household.diaryKeepers', 'diaryKeeper')
            ->leftJoin('diaryKeeper.user', 'user')
            ->leftJoin('diaryKeeper.diaryDays', 'diaryDays')
            ->leftJoin('diaryDays.journeys', 'journeys')
            ->leftJoin('journeys.stages', 'stages')
            ->orderBy('household.diaryWeekStartDate', 'ASC')
            ->addOrderBy('household.submittedAt', 'ASC')
            ->addOrderBy('diaryKeeper.number', 'ASC')
            ->addOrderBy('diaryDays.number', 'ASC')
            ->addOrderBy('journeys.startTime', 'ASC')
            ->addOrderBy('stages.number', 'ASC');
    }
}
