<?php

namespace App\Messenger\Job;

use App\Messenger\Job\Providers\ProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class JobHandler
{
    public function __construct(
        #[Autowire(service: 'cache.ghost.job.status')]
        protected CacheItemPoolInterface $statusCache,
        #[Autowire(service: 'cache.ghost.job.result')]
        protected CacheItemPoolInterface $resultCache,
        #[TaggedIterator('ghost.job.provider')]
        protected iterable               $providers,
        protected LoggerInterface        $logger,
    ) {}

    public function createJobStatus(Job $job): void
    {
        $statusCacheItem = $this->statusCache->getItem($job->getId());
        $statusCacheItem->set(new Status(State::NEW, job: $job));
        $this->statusCache->save($statusCacheItem);
    }

    public function jobFailed(Job $job, \Throwable $throwable): void
    {
        $statusCacheItem = $this->statusCache->getItem($job->getId());
        $statusCacheItem->set(new Status(State::FAILED, job: $job, errorMessage: $throwable->getMessage()));
        $this->statusCache->save($statusCacheItem);
    }
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(Job $job): void
    {
        $statusCacheItem = $this->statusCache->getItem($job->getId());
        /** @var Status $jobStatus */
        $jobStatus = $statusCacheItem->get();
        $jobStatus->setState(State::RUNNING);
        $this->statusCache->save($statusCacheItem);

        try {
            $provider = $this->getProvider($job->getProvider());
            $result = $provider($job);

            if ($result) {
                $jobResultCacheItem = $this->resultCache->getItem($job->getId());
                $jobResultCacheItem->set($result);
                $this->resultCache->save($jobResultCacheItem);

                $jobStatus
                    ->setState(State::COMPLETED)
                    ->setResult($jobResultCacheItem->getKey());

                // Log successful job completion
                $this->logger->info('Job completed successfully', [$job]);
            } else {
                $errorMessage = 'Failed to generate job data';
                $jobStatus
                    ->setState(State::FAILED)
                    ->setErrorMessage($errorMessage);

                // Log failure
                $this->logger->error($errorMessage, [$job]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Job failed with exception', [
                'job' => $job,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
            ]);

            $jobStatus
                ->setState(State::FAILED)
                ->setErrorMessage($e->getMessage());
        }
        $this->statusCache->save($statusCacheItem);
    }

    protected function getProvider(string $provider): ProviderInterface
    {
        foreach ($this->providers as $providerInstance) {
            if ($providerInstance::class === $provider) {
                return $providerInstance;
            }
        }
        throw new \UnexpectedValueException("No provider found for {$provider}");
    }
}
