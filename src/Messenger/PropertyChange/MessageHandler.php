<?php

namespace App\Messenger\PropertyChange;

use App\Entity\PropertyChangeLog;
use App\Repository\PropertyChangeLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MessageHandler implements MessageHandlerInterface
{
    protected PropertyChangeLogRepository $changeLogRepository;
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->changeLogRepository = $this->entityManager->getRepository(PropertyChangeLog::class);
    }

    public function __invoke(AbstractMessage $message): void
    {
        if ($message instanceof EntityChangeMessage) {
            $this->handleChangeMessage($message);
        } else if ($message instanceof EntityDeletionMessage) {
            $this->handleDeleteMessage($message);
        }
    }

    protected function handleChangeMessage(EntityChangeMessage $message): void
    {
        /** @var PropertyChangeLog[] $changeLogs */
        $changeLogs = $this->changeLogRepository->createQueryBuilder('cl')
            ->where('cl.entityId = :entityId')
            ->andWhere('cl.entityClass = :entityClass')
            ->andWhere('cl.propertyName IN (:fieldsChanged)')
            ->orderBy('cl.timestamp', 'DESC')
            ->setParameters([
                'entityId' => $message->getEntityId(),
                'entityClass' => $message->getEntityClass(),
                'fieldsChanged' => $message->getFieldsChanged()
            ])
            ->getQuery()
            ->execute();

        foreach($message->getFieldsChanged() as $field) {
            $filteredChangeLogs = array_filter($changeLogs, fn(PropertyChangeLog $cl) => $cl->getPropertyName() === $field);
            $keeps = self::getKeepsForChangeLogs($filteredChangeLogs);
//            dump('Processing '.$message->getEntityClass().' / '.$message->getEntityId().' / '.$field);
            foreach ($filteredChangeLogs as $changeLog) {
                if (!in_array($changeLog, $keeps)) {
//                    dump('- Removing '.$changeLog->getId());
                    $this->entityManager->remove($changeLog);
                }
            }
        }

        $this->entityManager->flush();
    }

    protected function handleDeleteMessage(EntityDeletionMessage $message): void
    {
        $this->changeLogRepository->createQueryBuilder('cl')
            ->delete()
            ->where('cl.entityId = :entityId')
            ->andWhere('cl.entityClass = :entityClass')
            ->setParameters([
                'entityId' => $message->getEntityId(),
                'entityClass' => $message->getEntityClass(),
            ])
            ->getQuery()
            ->execute();
    }

    public static function getKeepsForChangeLogs(array $changeLogs): array
    {
        $keeps = [];

        $mostRecentIsInt = null;
        $allChangesMadeByInterviewer = true;
        $allChangesMadeByDiaryKeeper = true;

        /** @var PropertyChangeLog $changeLog */
        foreach ($changeLogs as $changeLog) {
            if ($changeLog->getIsInterviewer()) {
                $allChangesMadeByDiaryKeeper = false;
            } else {
                $allChangesMadeByInterviewer = false;
            }

            if (!$allChangesMadeByDiaryKeeper && !$allChangesMadeByInterviewer) {
                break;
            }
        }

        /** @var PropertyChangeLog $changeLog */
        foreach ($changeLogs as $changeLog) {
            if ($mostRecentIsInt === null) {
                $mostRecentIsInt = $changeLog->getIsInterviewer();
                $keeps[] = $changeLog;

                if ($allChangesMadeByInterviewer || $allChangesMadeByDiaryKeeper) {
                    break;
                }

                continue;
            }

            if ($mostRecentIsInt && !$changeLog->getIsInterviewer()) {
                $keeps[] = $changeLog;
                break;
            }

            if (!$mostRecentIsInt && $changeLog->getIsInterviewer()) {
                $keeps[] = $changeLog;
                $mostRecentIsInt = true;
            }
        }

        return $keeps;
    }
}
