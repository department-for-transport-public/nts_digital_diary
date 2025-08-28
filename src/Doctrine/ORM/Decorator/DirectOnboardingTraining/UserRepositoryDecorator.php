<?php

namespace App\Doctrine\ORM\Decorator\DirectOnboardingTraining;

use App\Repository\UserRepository;
use App\Security\Voter\Onboarding\DirectOnboardingTrainingVoter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @method null loadUserByIdentifier(string $identifier)
 */
class UserRepositoryDecorator extends UserRepository
{
    public function __construct(ManagerRegistry $registry, protected Security $security)
    {
        parent::__construct($registry);
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        if ($this->isTraining() && array_keys($criteria) === ["username", "trainingInterviewer"]) {
            return [];
        }
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    public function findByForDirectOnboarding(array $criteria = []): array
    {
        if ($this->isTraining()) {
            return [];
        }
        return parent::findByForDirectOnboarding($criteria);
    }

    protected function isTraining(): bool
    {
        return $this->security->isGranted(DirectOnboardingTrainingVoter::DIRECT_ONBOARDING_TRAINING);
    }
}