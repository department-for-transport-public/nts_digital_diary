<?php

namespace App\Repository;

use App\Entity\DiaryKeeper;
use App\Entity\PropertyChangeLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PropertyChangeLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropertyChangeLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropertyChangeLog[]    findAll()
 * @method PropertyChangeLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyChangeLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyChangeLog::class);
    }

    public function getLogsForJourneysAndStagesBelongingTo(DiaryKeeper $diaryKeeper): array
    {
        $entityIds = [];

        foreach($diaryKeeper->getDiaryDays() as $diaryDay) {
            foreach($diaryDay->getJourneys() as $journey) {
                $entityIds[] = $journey->getId();
                foreach($journey->getStages() as $stage) {
                    $entityIds[] = $stage->getId();
                }
            }
        }

        return $this->getLogsForEntityIds($entityIds);
    }

    public function getLogsForEntityIds(array $entityIds): array
    {
        return $this
            ->createQueryBuilder('cl')
            ->select('cl.entityId, cl.propertyName, cl.propertyValue, cl.interviewerSerialId, cl.timestamp')
            ->where('cl.entityId IN (:entityIds)')
            ->orderBy('cl.timestamp', 'DESC')
            ->setParameter('entityIds', $entityIds)
            ->getQuery()
            ->getArrayResult();
    }
}
