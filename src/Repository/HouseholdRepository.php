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
            ->innerJoin('household.areaPeriod', 'areaPeriod')
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
            // This AreaPeriod innerJoin is required to exclude training AreaPeriods (excluded by the training filter)
            ->innerJoin('household.areaPeriod', 'areaPeriod')
            ->where('household.submittedAt >= :startTime')
            ->andWhere('household.submittedAt < :endTime')
            ->andWhere('diaryKeeper.diaryState IN (:states)')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->setParameter('states', array(DiaryKeeper::STATE_APPROVED, DiaryKeeper::STATE_DISCARDED))
            ->getQuery()
            ->execute();
    }

    public function findForExportByHouseholdSerials(array $householdSerials)
    {
        $em = $this->getEntityManager();

        // Preload related data in to the entity manager
        $qb = $this->createQueryBuilder('household');
        $qb
            ->innerJoin('household.areaPeriod', 'areaPeriod')
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
            ->innerJoin('household.areaPeriod', 'areaPeriod')
            ->where($qb->expr()->in(
                $qb->expr()->concat('areaPeriod.area', $qb->expr()->literal('/'), 'household.addressNumber', $qb->expr()->literal('/'), 'household.householdNumber'),
                ':serials'
            ))
            ->andWhere('diaryKeeper.diaryState IN (:states)')
            ->andWhere('household.submittedAt IS NOT NULL')
            ->setParameters([
                'serials' => $householdSerials,
                'states' => array(DiaryKeeper::STATE_APPROVED, DiaryKeeper::STATE_DISCARDED),
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

    /**
     * @return array<Household>
     */
    public function getSubmittedSurveysForPurge(\DateTime $before): array
    {
        return $this->createQueryBuilder('household')
            ->join('household.diaryKeepers', 'dk')
            ->join('dk.user', 'user')
            ->leftJoin('household.vehicles', 'vehicle')
            ->join('dk.diaryDays', 'day')
            ->leftJoin('day.journeys', 'journey')
            ->leftJoin('journey.stages', 'stage')
            ->where('household.submittedAt IS NOT NULL')
            ->andWhere('household.submittedAt < :before')
            ->getQuery()
            ->setParameter('before', $before)
            ->getResult();
    }

    public function findOneBySerial(int $area, int $addressNumber, int $householdNumber, bool $disallowAlreadySubmitted): ?Household
    {
        $qb = $this->createQueryBuilder('household')
            ->innerJoin('household.areaPeriod', 'areaPeriod')
            ->select('household, areaPeriod')
            ->where('areaPeriod.area = :area')
            ->andWhere('household.addressNumber = :address_number')
            ->andWhere('household.householdNumber = :household_number')
            ->andWhere('areaPeriod.trainingInterviewer IS NULL')
        ;

        if ($disallowAlreadySubmitted) {
            $qb->andWhere('household.submittedAt IS NULL');
        }

        return $qb
            ->setParameters([
                'area' => $area,
                'address_number' => $addressNumber,
                'household_number' => $householdNumber,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
