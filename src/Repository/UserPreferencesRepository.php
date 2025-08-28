<?php

namespace App\Repository;

use App\Entity\UserPreferences;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserPreferences|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPreferences|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPreferences[]    findAll()
 * @method UserPreferences[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPreferencesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPreferences::class);
    }
}
