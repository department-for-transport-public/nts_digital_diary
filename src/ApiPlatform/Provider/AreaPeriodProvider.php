<?php

namespace App\ApiPlatform\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\AreaPeriod;
use App\Repository\AreaPeriodRepository;

class AreaPeriodProvider implements ProviderInterface
{
    private AreaPeriodRepository $areaPeriodRepository;

    public function __construct(AreaPeriodRepository $areaPeriodRepository)
    {
        $this->areaPeriodRepository = $areaPeriodRepository;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?AreaPeriod
    {
        return $this->areaPeriodRepository->findOneBy(['area' => $uriVariables['area'] ?? null, 'year' => $uriVariables['year'] ?? null]);
    }
}