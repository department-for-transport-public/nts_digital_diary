<?php

namespace App\ApiPlatform\Provider;

use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Household;
use App\Entity\User;
use App\Repository\HouseholdRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

class HouseholdProvider implements ProviderInterface
{
    public function __construct(protected HouseholdRepository $householdRepository)
    {}

    /**
     * @return object|iterable<Household>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): null|array|object
    {
        if ($operation instanceof GetCollection) {
            return $this->householdRepository
                ->createQueryBuilder('household')
                ->select('household, areaPeriod, diaryKeeper, user')
                ->join('household.areaPeriod', 'areaPeriod')
                ->leftJoin('household.diaryKeepers', 'diaryKeeper')
                ->leftJoin('diaryKeeper.user', 'user')
                ->where('areaPeriod.trainingInterviewer IS NULL')
                ->orderBy('areaPeriod.area, household.addressNumber, household.householdNumber')
                ->getQuery()
                // Disable annoying eager
                ->setFetchMode(User::class, 'preferences', ClassMetadata::FETCH_LAZY)
                ->getResult();
        }

        throw new OperationNotFoundException();
    }
}
