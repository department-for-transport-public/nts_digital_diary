<?php

namespace App\EventSubscriber\Security;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHashSubscriber implements EventSubscriber
{
    protected UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        // Using a closure so that we have easy access to the already-fetched unitOfWork and entityManager
        $updatePasswordIfRequired = function(User $user) use ($entityManager, $unitOfWork): void {
            $classMetadata = $entityManager->getClassMetadata(User::class);
            $plainPassword = $user->getPlainPassword();

            $user->clearPlainPassword();
            if ($plainPassword !== null) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user
                    ->setPassword($hashedPassword)
                    ->setPasswordResetCode(null);
            }

            $unitOfWork->recomputeSingleEntityChangeSet($classMetadata, $user);
        };

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User) {
                $updatePasswordIfRequired($entity);
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                $updatePasswordIfRequired($entity);
            }
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush
        ];
    }
}