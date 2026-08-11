<?php

namespace App\Repository;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Utility\DiaryKeeperFeaturesService;
use App\Utility\ItemByFrequencyHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @method DiaryKeeper|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiaryKeeper[]    findAll()
 * @method DiaryKeeper[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiaryKeeperRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        protected Security $security,
        protected DiaryKeeperFeaturesService $featuresService,
    ) {
        parent::__construct($registry, DiaryKeeper::class);
    }

    public function getQueryBuilderForShareJourneyWhoWith(): QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $this->createQueryBuilder('diary_keeper')
            ->leftJoin('diary_keeper.household', 'household')
            ->where('household = :household')
            ->andWhere('diary_keeper <> :diaryKeeper')
            ->setParameter('household', $user->getDiaryKeeper()->getHousehold())
            ->setParameter('diaryKeeper', $user->getDiaryKeeper());
    }

    public function findByInterviewer(Interviewer $interviewer)
    {
        return $this->createQueryBuilder('dk')
            ->join('dk.household', 'h')
            ->andWhere('h.interviewer = :interviewer')
            ->setParameter('interviewer', $interviewer)
            ->orderBy('dk.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user, bool $includeStages = false): ?DiaryKeeper
    {
        try {
            $qb = $this->createQueryBuilder('dk')
                ->select('dk, hh, area, days, journeys' . ($includeStages ? ', stages' : ''))
                ->where('dk.id = :id')
                ->leftJoin('dk.household', 'hh')
                ->leftJoin('hh.areaPeriod', 'area')
                ->leftJoin('dk.diaryDays', 'days')
                ->leftJoin('days.journeys', 'journeys');

            if ($includeStages) {
                $qb->leftJoin('journeys.stages', 'stages');
            }

            return $qb
                ->setParameter('id', $user->getDiaryKeeper()->getId())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    public function findForUniqueConstraint(array $criteria): array
    {
        $qb = $this->createQueryBuilder('ap');

        foreach ($criteria as $field => $criterion) {
            $qb
                ->andWhere("ap.{$field} = :{$field}")
                ->setParameter($field, $criterion);
        }

        return $qb->getQuery()->execute();
    }

    public function getChangeProxyChoices(DiaryKeeper $diaryKeeper): array
    {
        return $this->getProxyChoices($diaryKeeper->getHousehold(), $diaryKeeper);
    }

    /**
     * @return array | DiaryKeeper[]
     */
    public function getOnBoardingProxyChoices(DiaryKeeper $diaryKeeperForExclusion): array
    {
        return $this->getProxyChoices($diaryKeeperForExclusion->getHousehold(), $diaryKeeperForExclusion);
    }

    private function getProxyChoices(Household $household, DiaryKeeper $diaryKeeperForExclusion): array
    {
        $qb = $this->createQueryBuilder('dk')
            ->leftJoin('dk.user', 'u')
            ->where('dk.household = :household')
            ->setParameter('household', $household)
            ->orderBy('dk.number', 'ASC');

        if ($diaryKeeperForExclusion->getId()) {
            $qb->andWhere('dk <> :exclude')
                ->setParameter('exclude', $diaryKeeperForExclusion);
        }

        return $qb->getQuery()->execute();
    }

    public function getCommonLocations(DiaryKeeper $diaryKeeper): array
    {
        $entityManager = $this->getEntityManager();
        $dayExpression = 'dd.number >= 0';

        // N.B. min(j.id) is used as an orderBy parameter to enforce a consistent result order whilst appearing random
        //      (i.e. avoids picking the results alphabetically)
        $starts = $entityManager->createQueryBuilder()
            ->select('j.startLocation, count(j.startLocation) as locCount, min(j.id) as minId')
            ->from(Journey::class, 'j')
            ->join('j.diaryDay', 'dd')
            ->where('dd.diaryKeeper = :dk')
            ->andWhere('j.startLocation is not null')
            ->andWhere($dayExpression)
            ->groupBy('j.startLocation')
            ->orderBy(new OrderBy('count(j.startLocation)', 'DESC'))
            ->addOrderBy('minId')
            ->setParameter('dk', $diaryKeeper)
            ->getQuery()
            ->execute();

        $ends = $entityManager->createQueryBuilder()
            ->select('j.endLocation, count(j.endLocation) as locCount, min(j.id) as minId')
            ->from(Journey::class, 'j')
            ->join('j.diaryDay', 'dd')
            ->where('dd.diaryKeeper = :dk')
            ->andWhere('j.endLocation is not null')
            ->andWhere($dayExpression)
            ->groupBy('j.endLocation')
            ->orderBy(new OrderBy('count(j.endLocation)', 'DESC'))
            ->addOrderBy('minId')
            ->setParameter('dk', $diaryKeeper)
            ->getQuery()
            ->execute();

        $mostRecent = $entityManager->createQueryBuilder()
            ->select('j.endLocation')
            ->from(Journey::class, 'j')
            ->join('j.diaryDay', 'dd')
            ->where('dd.diaryKeeper = :dk')
            ->andWhere($dayExpression)
            ->orderBy('j.id', 'DESC')
            ->setParameter('dk', $diaryKeeper)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        $helper = new ItemByFrequencyHelper();
        $helper->addEntries($starts, 'startLocation', 'locCount');
        $helper->addEntries($ends, 'endLocation', 'locCount');

        $results = $helper->getTopN(3);

        if ($mostRecent) {
            $name = $mostRecent['endLocation'];

            if ($name && !in_array($name, $results)) {
                array_unshift($results, $name);
            }
        }

        return $results;
    }

    public function findVehiclesNamedByDiaryKeeper(DiaryKeeper $diaryKeeper, ?int $methodCode, int $maxResults = 3): array {
        $query = $this->createQueryBuilder('dk')
            ->select('s.vehicleOther', 'count(s.vehicleOther) AS count')
            ->where('dk.id = :id');

        if ($methodCode === null) {
            $query = $query->andWhere('m.code IS NULL');
        } else {
            $query = $query
                ->andWhere('m.code = :methodCode')
                ->setParameter('methodCode', $methodCode);
        }

        $query = $query
            ->andWhere('s.vehicleOther IS NOT NULL')
            ->leftJoin('dk.diaryDays', 'day')
            ->leftJoin('day.journeys', 'j')
            ->leftJoin('j.stages', 's')
            ->leftJoin('s.method', 'm')
            ->groupBy('s.vehicleOther')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->setParameter('id', $diaryKeeper->getId());

        $vehicles = array_map(fn(array $x) => $x['vehicleOther'], $query->execute());

        return array_slice($vehicles, 0, $maxResults);
    }
}
