<?php

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Attribute\DisableTrainingAreaPeriodFilter;
use App\Doctrine\ORM\Filter\TrainingAreaPeriodFilter;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiDoctrineFilterSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws ReflectionException
     */
    public function preRead(RequestEvent $event)
    {
        $attributes = $event->getRequest()->attributes;
        // Only enable the filter for API requests
        if ($attributes->get('_firewall_context') !== 'security.firewall.map.context.api')
        {
            return;
        }

        $reflection = $attributes->get('_api_resource_class')
            ? new ReflectionClass($attributes->get('_api_resource_class'))
            : null;
        if ($reflection && !empty($reflection->getAttributes(DisableTrainingAreaPeriodFilter::class)))
        {
            return;
        }

        $this->entityManager->getFilters()->enable(TrainingAreaPeriodFilter::FILTER_NAME);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['preRead', EventPriorities::PRE_READ],
        ];
    }
}