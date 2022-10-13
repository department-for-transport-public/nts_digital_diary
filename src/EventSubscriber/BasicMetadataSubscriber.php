<?php

namespace App\EventSubscriber;

use App\Entity\BasicMetadata;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;

class BasicMetadataSubscriber implements EventSubscriber
{
    protected Security $security;
    protected UserRepository $userRepository;
    protected ?string $userString;

    public function __construct(Security $security, UserRepository $userRepository)
    {
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->userString = null;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $setBasicMetaDataIfRelevant = function(object $entity, string $field) use ($entityManager): void {
            if ($entity instanceof BasicMetadata) {
                if (!$this->userString) {
                    // Instantiated inside this block, as it will throw an Exception when called outside of certain
                    // very strict contexts (i.e. must be a User editing one of a few types of entity. No OtpUsers etc)
                    $this->userString = $this->getUserString();
                }

                $accessor = PropertyAccess::createPropertyAccessor();
                $accessor->setValue($entity, "set{$field}At", new \DateTime());
                $accessor->setValue($entity, "set{$field}By", $this->userString);
                $classMetadata = $entityManager->getClassMetadata(get_class($entity));
                $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($classMetadata, $entity);
            }
        };

        $stateChanged = false;
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $stateChanged &= $setBasicMetaDataIfRelevant($entity, 'Created');
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $stateChanged &= $setBasicMetaDataIfRelevant($entity, 'Modified');
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush
        ];
    }

    protected function getUserString(): string
    {
        $token = $this->security->getToken();

        if (!$token) {
            return 'system';
        }

        // Needed to do this assignment inline, so that PHPStorm would understand the type implications
        $user = $token->getUser();
        $originalUser = ($token instanceof SwitchUserToken) ?  $token->getOriginalToken()->getUser() : null;

        if (!$user) {
            return 'system';
        }

        if (!$user instanceof User || ($originalUser && !$originalUser instanceof User)) {
            // We should never get here as we're filtering for certain entities (Journey / Stage) which can only
            // be edited in the context of being logged in as a User (and not e.g. OtpUser)
            throw new \RuntimeException('Unexpected User type');
        }

        if ($originalUser) {
            // The user retrieved from originalToken->getUser() isn't fully hydrated, so we need to re-fetch it...
            $originalUser = $this->userRepository->findOneBy(['username' => $originalUser->getUserIdentifier()]);
            if ($originalUser->hasRole(User::ROLE_INTERVIEWER)) {
                return "interviewer:{$originalUser->getInterviewer()->getSerialId()}";
            } else if ($originalUser->hasRole(User::ROLE_DIARY_KEEPER)) {
                return "proxy:{$originalUser->getDiaryKeeper()->getName()}";
            }
        } else if ($user->hasRole(User::ROLE_DIARY_KEEPER)) {
            return 'self';
        }

        // If we've gotten here, then we're editing the entity in an unexpected context.
        throw new \RuntimeException('Unexpected User / context');
    }
}