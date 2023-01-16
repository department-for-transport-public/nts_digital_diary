<?php

namespace App\EventSubscriber;

use App\Entity\PropertyChangeLog;
use App\Entity\PropertyChangeLoggable;
use App\Entity\User;
use App\Messenger\PropertyChange\EntityChangeMessage;
use App\Messenger\PropertyChange\EntityDeletionMessage;
use App\Serializer\ChangeSet\AbstractChangeSetNormalizer;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PropertyChangeLogEventSubscriber implements EventSubscriber
{
    protected EntityManagerInterface $entityManager;
    protected MessageBusInterface $messageBus;
    protected NormalizerInterface $normalizer;
    protected Security $security;

    public function __construct(MessageBusInterface $messageBus, Security $security, EntityManagerInterface $entityManager, NormalizerInterface $normalizer)
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->normalizer = $normalizer;
        $this->security = $security;
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $token = $this->security->getToken();

        if ($token === null) {
            return;
        }

        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $interviewerSerialId = $this->getInterviewerSerialId();

        $changeLogMetadata = $entityManager->getClassMetadata(PropertyChangeLog::class);

        foreach($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof PropertyChangeLoggable) {
                continue;
            }

            [$changeLogs, $fieldsChanged] = $this->logChanges($unitOfWork, $interviewerSerialId, $entity);
            foreach($changeLogs as $changeLog) {
                $entityManager->persist($changeLog);
                $unitOfWork->computeChangeSet($changeLogMetadata, $changeLog);
            }
        }

        foreach($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof PropertyChangeLoggable) {
                continue;
            }

            [$changeLogs, $fieldsChanged] = $this->logChanges($unitOfWork, $interviewerSerialId, $entity);
            foreach($changeLogs as $changeLog) {
                $entityManager->persist($changeLog);
                $unitOfWork->computeChangeSet($changeLogMetadata, $changeLog);
            }

            if (!empty($fieldsChanged)) {
                $this->messageBus->dispatch((new EntityChangeMessage($entity->getId(), get_class($entity), $fieldsChanged)));
            }
        }

        foreach($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof PropertyChangeLoggable) {
                $this->messageBus->dispatch(new EntityDeletionMessage($entity->getId(), get_class($entity)));
            }
        }
    }

    protected function logChanges(UnitOfWork $unitOfWork, ?string $interviewerSerialId, object $entity): array
    {
        $entityClass = get_class($entity);
        $changeLogTemplate = (new PropertyChangeLog())
            ->setEntityId($entity->getId())
            ->setEntityClass($entityClass)
            ->setTimestamp(new \DateTime())
            ->setInterviewerSerialId($interviewerSerialId);

        $changeLogEntities = [];
        $fieldsChanged = [];

        $changeSet = $unitOfWork->getEntityChangeSet($entity);
        $changeSet = $this->normalizer->normalize($changeSet, null, [AbstractChangeSetNormalizer::CHANGE_SET_ENTITY_KEY => $entity]);

        foreach($changeSet as $field => [$oldValue, $newValue]) {
            $fieldsChanged[] = $field;

            $changeLogEntities[] = (clone $changeLogTemplate)
                ->setPropertyName($field)
                ->setPropertyValue($newValue);
        }

        return [$changeLogEntities, $fieldsChanged];
    }

    protected function getInterviewerSerialId(): ?string {
        $token = $this->security->getToken();
        /** @var User $actualUser */
        $actualUser = $token instanceof SwitchUserToken ?
            $token->getOriginalToken()->getUser() :
            $token->getUser();

        if (!$actualUser instanceof User || !$actualUser->hasRole(User::ROLE_INTERVIEWER)) {
            return null;
        }

        // we need to reload the user in order to access the interviewer
        $actualUser = $this->entityManager->find(get_class($actualUser), $actualUser->getId());
        return $actualUser->getInterviewer()->getSerialId();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }
}