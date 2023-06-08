<?php

namespace App\Repository;

use App\Entity\AreaPeriod;
use App\Entity\Interviewer;
use App\Entity\InterviewerTrainingRecord;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method InterviewerTrainingRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method InterviewerTrainingRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method InterviewerTrainingRecord[]    findAll()
 * @method InterviewerTrainingRecord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterviewerTrainingRecordRepository extends ServiceEntityRepository
{
    private Security $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, InterviewerTrainingRecord::class);
        $this->security = $security;
    }

    /**
     * @return InterviewerTrainingRecord[]
     */
    public function findLatestForInterviewer(Interviewer $interviewer): array
    {
        $results = $this->createQueryBuilder('itr')
            ->select('itr')
            ->leftJoin(
                InterviewerTrainingRecord::class,
                'itr2', Expr\Join::WITH,
                'itr.interviewer = itr2.interviewer AND itr.moduleName = itr2.moduleName AND itr.createdAt < itr2.createdAt'
            )
            ->where('itr.interviewer = :interviewer')->setParameter('interviewer', $interviewer)
            ->andWhere('itr2.createdAt IS NULL')
            ->getQuery()
            ->execute()
            ;
        usort(
            $results,
            fn(InterviewerTrainingRecord $a, InterviewerTrainingRecord $b) => $a->getModuleNumber() <=> $b->getModuleNumber()
        );
        return $results;
    }

    public function findLatestByModuleName(string $moduleName): ?InterviewerTrainingRecord
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $this->createQueryBuilder('itr')
            ->where('itr.moduleName = :moduleName')->setParameter('moduleName', $moduleName)
            ->andWhere('itr.interviewer = :interviewer')->setParameter('interviewer', $user->getInterviewer())
            ->orderBy('itr.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
