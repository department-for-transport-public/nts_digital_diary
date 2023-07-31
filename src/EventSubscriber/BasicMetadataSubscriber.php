<?php

namespace App\EventSubscriber;

use App\Entity\ApiUser;
use App\Entity\BasicMetadataInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\ImpersonatorAuthorizationChecker;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class BasicMetadataSubscriber implements EventSubscriber
{
    protected UserRepository $userRepository;
    protected ?string $userString;
    private ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker;
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker, ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker, UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->userString = null;
        $this->impersonatorAuthorizationChecker = $impersonatorAuthorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $setBasicMetaDataIfRelevant = function(object $entity, string $field) use ($entityManager): void {
            if ($entity instanceof BasicMetadataInterface) {
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
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return 'system';
        }

        // Needed to do this assignment inline, so that PHPStorm would understand the type implications
        $user = $token->getUser();
        $originalUser = ($token instanceof SwitchUserToken) ?  $token->getOriginalToken()->getUser() : null;

        if (!$user) {
            return 'system';
        }

        // while not actually relevant, this is needed to prevent FK integrity issues when deleting
        // an interviewer who has training modules with shared journeys
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return "admin:{$user->getUserIdentifier()}";
        }

        if (
            (!$user instanceof User || ($originalUser && !$originalUser instanceof User))
            && (!$user instanceof ApiUser)
        ) {
            // We should never get here as we're filtering for certain entities (Journey / Stage) which can only
            // be edited in the context of being logged in as a User (and not e.g. OtpUser)
            throw new \RuntimeException('Unexpected User type');
        }

        if ($originalUser) {
            // The user retrieved from originalToken->getUser() isn't fully hydrated, so we need to re-fetch it...
            $originalUser = $this->userRepository->loadUserByIdentifier($originalUser->getUserIdentifier());
            if ($this->impersonatorAuthorizationChecker->isGranted(User::ROLE_INTERVIEWER)) {
                return "interviewer:{$originalUser->getInterviewer()->getSerialId()}";
            } else if ($this->impersonatorAuthorizationChecker->isGranted(User::ROLE_DIARY_KEEPER)) {
                return "proxy:{$originalUser->getDiaryKeeper()->getName()}";
            }
        } else if ($this->authorizationChecker->isGranted(User::ROLE_DIARY_KEEPER)) {
            return 'self';
        } else if ($this->authorizationChecker->isGranted(User::ROLE_INTERVIEWER)) {
            return "interviewer:{$user->getUserIdentifier()}";
        }

        // If we've gotten here, then we're editing the entity in an unexpected context.
        throw new \RuntimeException('Unexpected User / context');
    }
}