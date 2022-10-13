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
}
