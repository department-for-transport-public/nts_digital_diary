<?php

namespace App\Repository;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getDiaryKeeperJourneysAndStages(string $username): ?DiaryKeeper {
        $user = $this->createQueryBuilder('u')
            ->select('u, dk, h, d, j, s, m, v')
            ->innerJoin('u.diaryKeeper', 'dk')
            ->leftJoin('dk.diaryDays', 'd')
            ->leftJoin('dk.household', 'h')
            ->leftJoin('d.journeys', 'j')
            ->leftJoin('j.stages', 's')
            ->leftJoin('s.method', 'm')
            ->leftJoin('s.vehicle', 'v')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();

        return $user ? $user->getDiaryKeeper() : null;
    }

    public function isExistingUserWithEmailAddress($emailOverride, string $excludingUserId = null): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u')
            ->where('u.username = :email')
            ->setParameter('email', $emailOverride);

        if ($excludingUserId) {
            $qb = $qb
                ->andWhere('u.id != :id')
                ->setParameter('id', $excludingUserId);
        }

        $results = $qb->getQuery()->execute();

        return count($results) > 0;
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->loadUserByUsername($identifier);
    }

    public function loadUserByUsername(string $username): ?User
    {
        try {
            return $this->createQueryBuilder('u')
                ->where('u.username = :username')
                ->setParameter('username', $username)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * Fetches user with everything needed to generate a serial ID (supports both int or dk)
     */
    public function loadUserForSerialInformation(string $identifier): ?User
    {
        try {
            return $this->createQueryBuilder('user')
                ->select('user, dk, int, household, area')
                ->leftJoin('user.diaryKeeper', 'dk')
                ->leftJoin('dk.household', 'household')
                ->leftJoin('household.areaPeriod', 'area')
                ->leftJoin('user.interviewer', 'int')
                ->where('user.username = :username')
                ->setParameter('username', $identifier)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * Returns an array of users whose household has been submitted before the given date, and who have not previously
     * had their email address purged.
     *
     * @return array<User>
     */
    public function getUsersForEmailPurge(\DateTime $before): array
    {
        return $this->createQueryBuilder('user')
            ->select('user, dk, household')
            ->join('user.diaryKeeper', 'dk')
            ->join('dk.household', 'household')
            ->where('household.submittedAt IS NOT NULL')
            ->andWhere('household.submittedAt < :before')
            ->andWhere('user.emailPurgeDate IS NULL')
            ->getQuery()
            ->setParameter('before', $before)
            ->getResult();
    }
}
