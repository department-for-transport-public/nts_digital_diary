<?php

namespace App\Repository;

use App\Entity\DiaryDay;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @method DiaryDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiaryDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiaryDay[]    findAll()
 * @method DiaryDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiaryDayRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        protected readonly Security $security,
    ) {
        parent::__construct($registry, DiaryDay::class);
    }

    public function getJourneyCountForUser(): int
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $qb = $this->createQueryBuilder('diary_day')
            ->leftJoin('diary_day.journeys', 'journey')
            ->select('count(journey) as count')
            ->where('diary_day.diaryKeeper = :diary_keeper')
            ->setParameter('diary_keeper', $user->getDiaryKeeper());
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function getQueryBuilderForRepeatJourneySourceDay(): QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $qb = $this->createQueryBuilder('diary_day')
            ->leftJoin('diary_day.diaryKeeper', 'diary_keeper')
            ->where('diary_keeper = :diary_keeper')
            ->andWhere('diary_day.number <= 7')
            ->andWhere('diary_day.number >= 0')
            ->orderBy('diary_day.number')
            ->setParameter('diary_keeper', $user->getDiaryKeeper());

        return $qb;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getQueryBuilderForReturnJourneyTargetDay(string $sourceJourneyId): QueryBuilder
    {
        /** @var DiaryDay $sourceDay */
        $sourceDay = $this->createQueryBuilder('diary_day')
            ->leftJoin('diary_day.journeys', 'journeys')
            ->where('journeys.id = :journeyId')
            ->setParameter('journeyId', $sourceJourneyId)
            ->getQuery()
            ->getSingleResult();

        /** @var User $user */
        $user = $this->security->getUser();
        return $this->createQueryBuilder('diary_day')
            ->leftJoin('diary_day.diaryKeeper', 'diary_keeper')
            ->where('diary_keeper = :diary_keeper')
            ->andWhere('diary_day.number >= :number')
            ->orderBy('diary_day.number')
            ->setParameter('diary_keeper', $user->getDiaryKeeper())
            ->setParameter('number', $sourceDay->getNumber());
    }
}
