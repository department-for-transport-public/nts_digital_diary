<?php

namespace App\EventSubscriber\Test;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

class PreFlushEventSubscriber implements EventSubscriber
{
    private bool $isTestEnvironment;

    public function __construct(string $appEnvironment)
    {
        $this->isTestEnvironment = ($appEnvironment === 'test');
    }

    /**
     * @throws DbalException
     * @throws DbalDriverException
     */
    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        if (!$this->isTestEnvironment) return;
        if ($eventArgs->getEntityManager()->getConnection()->getDriver()->getDatabasePlatform()->getName() !== 'sqlite') return;

        $eventArgs->getEntityManager()->getConnection()
            ->prepare("PRAGMA foreign_keys = ON")
            ->executeStatement();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::preFlush,
        ];
    }
}