<?php

namespace App\Repository;

use App\Entity\Interviewer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Interviewer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Interviewer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Interviewer[]    findAll()
 * @method Interviewer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterviewerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interviewer::class);
    }

    public function findOneByUser(User $user): ?Interviewer
    {
        try {
            $qb = $this->createQueryBuilder('int')
                ->select('int, areas, hh, dk, user')
                ->leftJoin('int.areaPeriods', 'areas')
                ->leftJoin('areas.households', 'hh')
                ->leftJoin('hh.diaryKeepers', 'dk')
                ->leftJoin('dk.user', 'user')
                ->where('int.id = :id')
                ->orderBy('areas.area', 'ASC')
                ->addOrderBy('hh.addressNumber', 'ASC')
                ->addOrderBy('hh.householdNumber', 'ASC');

            return $qb
                ->setParameters([
                    'id' => $user->getInterviewer()->getId(),
                ])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
