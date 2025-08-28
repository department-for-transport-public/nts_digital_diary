<?php

namespace App\EventSubscriber\Security;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: Events::onFlush)]
class UserPasswordHashSubscriber
{
    public function __construct(protected UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getObjectManager();
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
}
