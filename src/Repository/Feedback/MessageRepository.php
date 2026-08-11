<?php

namespace App\Repository\Feedback;

use App\Entity\Feedback\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function getQueryBuilderForAdminViewListPage(bool $showArchive = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('message');
        $qb->select('message')
            ->andWhere('message.state != :state_new')
            ->setParameter('archiveStates', [Message::STATE_CLOSED, Message::STATE_REJECTED])
            ->setParameter('archiveDate', new \DateTime('-3 months'))
            ->setParameter('state_new', Message::STATE_NEW);
        if ($showArchive) {
            $qb->andWhere(new Expr\Andx([
                "message.state in (:archiveStates)",
                "message.sent <= :archiveDate"
            ]));
        } else {
            $qb->andWhere(new Expr\Orx([
                "message.state not in (:archiveStates)",
                "message.sent > :archiveDate"
            ]));
        }
        return $qb;
    }

    // /**
    //  * @return FeedbackMessage[] Returns an array of FeedbackMessage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FeedbackMessage
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
