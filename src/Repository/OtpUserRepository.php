<?php

namespace App\Repository;

use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method OtpUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method OtpUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method OtpUser[]    findAll()
 * @method OtpUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OtpUserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OtpUser::class);
    }

    /**
     * Used by firewall, needed for login case insensitivity
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $qb =  $this->createQueryBuilder('u');
        $qb
            ->leftJoin('u.household', 'h')
            ->where('LOWER(u.userIdentifier) = :identifier')
            ->andWhere($qb->expr()->orX('h.isOnboardingComplete <> :onboardingComplete', 'h.id is null'))
            ->setParameters([
                'identifier' => strtolower($identifier),
                'onboardingComplete' => true,
            ])
            ;
        return $qb
            ->getQuery()
            ->getOneOrNullResult();

    }

    /**
     * @throws NonUniqueResultException
     */
    public function loadUserByUsername(string $username): ?UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function findForInterviewerDashboard(AreaPeriod $areaPeriod): array
    {
        $qb =  $this->createQueryBuilder('u');
        $qb
            ->leftJoin('u.household', 'h')
            ->where('u.areaPeriod = :areaPeriod')
            ->andWhere($qb->expr()->orX('h.isOnboardingComplete <> :onboardingComplete', 'h.id is null'))
            ->setParameters([
                'areaPeriod' => $areaPeriod,
                'onboardingComplete' => true,
            ])
        ;
        return $qb
            ->getQuery()
            ->execute();
    }

    /**
     * @return array<OtpUser>
     */
    public function findUsersWithAreaPeriodBefore(int $year, int $month): array
    {
        return $this->createQueryBuilder('user')
            ->select('user, areaPeriod')
            ->join('user.areaPeriod', 'areaPeriod')
            ->where('areaPeriod.year < :year OR (areaPeriod.year = :year AND areaPeriod.month < :month)')
            ->setParameters([
                'year' => $year,
                'month' => $month,
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<OtpUser>
     */
    public function findUsersOnboardedBefore(\DateTime $before): array
    {
        return $this->createQueryBuilder('user')
            ->join('user.household', 'household')
            ->where('household.diaryWeekStartDate < :before')
            ->andWhere('household.isOnboardingComplete = 1')
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult();
    }
}
