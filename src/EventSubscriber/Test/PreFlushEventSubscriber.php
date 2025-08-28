<?php

namespace App\EventSubscriber\Test;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preFlush)]
class PreFlushEventSubscriber
{
    protected readonly bool $isTestEnvironment;

    public function __construct(string $appEnvironment)
    {
        $this->isTestEnvironment = ($appEnvironment === 'test');
    }

    /**
     * @throws DbalException
     */
    public function preFlush(PreFlushEventArgs $eventArgs): void
    {
        if (!$this->isTestEnvironment) return;
        $entityManager = $eventArgs->getObjectManager();

        if ($entityManager->getConnection()->getDatabasePlatform() instanceof  SqlitePlatform) {
            return;
        }

        $entityManager->getConnection()
            ->prepare("PRAGMA foreign_keys = ON")
            ->executeStatement();
    }
}
