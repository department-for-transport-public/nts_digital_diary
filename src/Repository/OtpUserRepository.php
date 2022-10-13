<?php

namespace App\Repository;

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

    // /**
    //  * @return OneTimePassword[] Returns an array of OneTimePassword objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OneTimePassword
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

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
}
