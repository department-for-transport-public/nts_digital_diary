<?php

namespace App\Repository\Journey;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Journey\Journey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnexpectedResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Journey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Journey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Journey[]    findAll()
 * @method Journey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JourneyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journey::class);
    }

    public function findByJourneyId(string $id): ?Journey
    {
        try {
            return $this->createQueryBuilder('j')
                ->select('j, d, dk, s, m, h, a')
                ->leftJoin('j.diaryDay', 'd')
                ->leftJoin('d.diaryKeeper', 'dk')
                ->leftJoin('j.stages', 's')
                ->leftJoin('s.method', 'm')
                ->leftJoin('dk.household', 'h')
                ->leftJoin('h.areaPeriod', 'a')
                ->where('j.id = :id')
                ->setParameters([
                    'id' => $id,
                ])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function getSharedFromName(Journey $journey): ?string
    {
        $sharedFrom = $journey->getSharedFrom();
        if (!$sharedFrom) {
            return null;
        }

        try {
            // N.B. Calling ->getId() triggers proxy load which is not what we want!
            $sharedFromId = $this->getEntityManager()
                ->getUnitOfWork()
                ->getEntityIdentifier($sharedFrom)['id'];
        } catch (EntityNotFoundException $e) {
            return null;
        }

        $query = $this->createQueryBuilder('j')
            ->select('dk.name')
            ->leftJoin('j.diaryDay', 'dd')
            ->leftJoin('dd.diaryKeeper', 'dk')
            ->where('j.id = :id')
            ->setParameters(['id' => $sharedFromId])
            ->getQuery();

        try {
            return $query->getSingleScalarResult();
        } catch (UnexpectedResultException $e) {
            return null;
        }
    }

    public function getSharedToNames(Journey $journey): ?string
    {
        $query = $this->createQueryBuilder('j')
            ->select('dk.name')
            ->leftJoin('j.diaryDay', 'dd')
            ->leftJoin('dd.diaryKeeper', 'dk')
            ->where('j.sharedFrom = :sourceJourney')
            ->setParameters(['sourceJourney' => $journey])
            ->getQuery();

        $names = array_map(
            fn($x) => $x['name'],
            $query->getArrayResult()
        );

        return empty($names) ?
            null :
            join(', ', $names);
    }

    public function findByHousehold(Household $household): Collection
    {
        return $this->createQueryBuilder('j')
            ->join('j.diaryDay', 'dd')
            ->join('dd.diaryKeeper', 'dk')
            ->andWhere('dk.household = :household')
            ->setParameter('household', $household)
            ->orderBy('j.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDiaryKeeper(DiaryKeeper $diaryKeeper): Collection
    {
        return $this->createQueryBuilder('j')
            ->join('j.diaryDay', 'dd')
            ->andWhere('dd.diaryKeeper = :diaryKeeper')
            ->setParameter('diaryKeeper', $diaryKeeper)
            ->orderBy('dd.number, j.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getQueryBuilderByDayId(string $dayId): QueryBuilder
    {
        return $this->createQueryBuilder('journey')
            ->leftJoin('journey.diaryDay', 'diary_day')
            ->where('diary_day.id = :day_id')
            ->setParameter('day_id', $dayId)
            ->orderBy('journey.startTime');
    }
}
