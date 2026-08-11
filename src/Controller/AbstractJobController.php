<?php

namespace App\Controller;

use App\Messenger\Job\Status;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class AbstractJobController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'cache.ghost.job.status')]
        protected CacheItemPoolInterface $statusCache,
        #[Autowire(service: 'cache.ghost.job.result')]
        protected CacheItemPoolInterface $resultCache,
        protected MessageBusInterface    $messageBus
    ) {}
    

    protected function getJobStatus(string $jobId): ?Status
    {
        if (!preg_match('/^[A-Z0-9]{26}$/', $jobId)) {
            throw new NotFoundHttpException('Invalid job ID');
        }

        $jobStatus = null;
        $item = $this->statusCache->getItem($jobId);

        if ($item->isHit()) {
            $jobStatus = $item->get();
        }

        return $jobStatus instanceof Status ? $jobStatus : null;
    }

    protected function getJobResultFromCache(Status $status): mixed
    {
        $item = $this->resultCache->getItem($status->getResult());

        if (!$item->isHit()) {
            throw new NotFoundHttpException('Job data not found');
        }

        return $item->get();
    }
}
