<?php

namespace App\Repository\Journey;

use App\Entity\Journey\Stage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stage[]    findAll()
 * @method Stage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stage::class);
    }

    public function findByStageId(string $id): ?Stage
    {
        try {
            return $this->createQueryBuilder('st')
                ->select('st, method, journey, day, vehicle')
                ->where('st.id = :id')
                ->leftJoin('st.method', 'method')
                ->leftJoin('st.journey', 'journey')
                ->leftJoin('journey.diaryDay', 'day')
                ->leftJoin('st.vehicle', 'vehicle')
                ->setParameters([
                    'id' => $id,
                ])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
