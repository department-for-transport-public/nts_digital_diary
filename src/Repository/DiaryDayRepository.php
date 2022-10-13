<?php

namespace App\Repository;

use App\Entity\DiaryDay;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method DiaryDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiaryDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiaryDay[]    findAll()
 * @method DiaryDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiaryDayRepository extends ServiceEntityRepository
{
    private Security $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, DiaryDay::class);
        $this->security = $security;
    }

    public function getQueryBuilderForRepeatJourneySourceDay(int $beforeOrIncludingDay = 7): QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $qb = $this->createQueryBuilder('diary_day')
            ->leftJoin('diary_day.diaryKeeper', 'diary_keeper')
            ->where('diary_keeper = :diary_keeper')
            ->orderBy('diary_day.number')
            ->setParameters([
                'diary_keeper' => $user->getDiaryKeeper(),
                'number' => $beforeOrIncludingDay,
            ]);
        if ($beforeOrIncludingDay === 0) {
            $qb->andWhere('diary_day.number = :number');
        } else {
            $qb
                ->andWhere('diary_day.number <= :number')
                ->andWhere('diary_day.number >= 1');
        }
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
            ->andWhere(new Expr\Comparison(
                'diary_day.number',
                $sourceDay->getNumber() === 0 ? Expr\Comparison::EQ : Expr\Comparison::GTE,
                ':number')
            )
            ->orderBy('diary_day.number')
            ->setParameters([
                'diary_keeper' => $user->getDiaryKeeper(),
                'number' => $sourceDay->getNumber(),
            ]);
    }
}
