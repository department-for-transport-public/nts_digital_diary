<?php

namespace App\Repository;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        if (array_key_exists('username', $criteria)) {
            throw new \RuntimeException('It is unsafe to use findOneBy(username), use loadUserByIdentifier instead');
        }

        return parent::findOneBy($criteria, $orderBy);
    }

    public function getDiaryKeeperJourneysAndStagesForTests(string $username): ?DiaryKeeper {
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
            // as this is for tests only, and not ones involving training, it's ok to filter on that
            ->andWhere('u.trainingInterviewer is null')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();

        return $user ? $user->getDiaryKeeper() : null;
    }

    public function canChangeEmailTo(string $email, string $excludingUserId = null): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u')
            ->where('u.username = :email')
            // trainingInterviewer IS NULL means that this method is only usable for *real* (i.e. non-training)
            // users. In training scenarios, changing email addresses is not allowed, so this shouldn't be a problem.
            ->andWhere('u.trainingInterviewer IS NULL')
            ->setParameter('email', $email);

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
        $noLoginPlaceholder = User::NO_LOGIN_PLACEHOLDER;
        try {
            return $this->createQueryBuilder('u')
                ->where('u.username = :username')
                ->andWhere(new Expr\Orx([
                    'u.trainingInterviewer is null',
                    // this will exclude users created using onboarding training (which are the ones that might clash)
                    new Expr\Andx(['u.trainingInterviewer is not null', "u.username LIKE '{$noLoginPlaceholder}:%'"])
                ]))
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
                // this method is only ever called with the impersonator's identifier
                // so it's ok to filter on trainingInterviewer
                ->andWhere('user.trainingInterviewer is null')
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
