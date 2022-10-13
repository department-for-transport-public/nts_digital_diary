<?php

namespace App\Repository\Journey;

use App\Entity\Journey\Method;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Method|null find($id, $lockMode = null, $lockVersion = null)
 * @method Method|null findOneBy(array $criteria, array $orderBy = null)
 * @method Method[]    findAll()
 * @method Method[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Method::class);
    }
}
