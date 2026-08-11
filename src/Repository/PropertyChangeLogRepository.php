<?php

namespace App\Repository;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
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

    public function deleteForEntityIds(array $entityIds): void
    {
        if (empty($entityIds)) {
            return;
        }

        $this->createQueryBuilder('cl')
            ->delete()
            ->where('cl.entityId IN (:entityIds)')
            ->setParameter('entityIds', $entityIds)
            ->getQuery()
            ->execute();
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

    public function getOrphanedStats(): array
    {
        $cutoff = (new \DateTime('200 days ago'))->modify('-1 week');
        $em = $this->getEntityManager();
        $stats = [];

        foreach ($this->getOrphanedEntityTypes() as $type => $entityClass) {
            $result = $em->createQuery(
                'SELECT COUNT(cl.id) AS cnt, MIN(cl.timestamp) AS earliest, MAX(cl.timestamp) AS latest
                 FROM App\Entity\PropertyChangeLog cl
                 WHERE cl.entityClass = :entityClass
                 AND cl.entityId NOT IN (SELECT e.id FROM ' . $entityClass . ' e)
                 AND cl.timestamp < :cutoff'
            )
                ->setParameter('entityClass', $entityClass)
                ->setParameter('cutoff', $cutoff)
                ->getSingleResult();

            $stats[] = [
                'type' => $type,
                'count' => (int) $result['cnt'],
                'earliest' => $result['earliest'] ? new \DateTime($result['earliest']) : null,
                'latest' => $result['latest'] ? new \DateTime($result['latest']) : null,
            ];
        }

        return $stats;
    }

    public function deleteOrphaned(): int
    {
        $cutoff = (new \DateTime('200 days ago'))->modify('-1 week');
        $em = $this->getEntityManager();
        $total = 0;

        foreach ($this->getOrphanedEntityTypes() as $entityClass) {
            $total += $em->createQuery(
                'DELETE FROM App\Entity\PropertyChangeLog cl
                 WHERE cl.entityClass = :entityClass
                 AND cl.entityId NOT IN (SELECT e.id FROM ' . $entityClass . ' e)
                 AND cl.timestamp < :cutoff'
            )
                ->setParameter('entityClass', $entityClass)
                ->setParameter('cutoff', $cutoff)
                ->execute();
        }

        return $total;
    }

    private function getOrphanedEntityTypes(): array
    {
        return [
            'Journey' => Journey::class,
            'Stage' => Stage::class,
        ];
    }
}
