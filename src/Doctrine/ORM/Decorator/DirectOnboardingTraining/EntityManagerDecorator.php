<?php

namespace App\Doctrine\ORM\Decorator\DirectOnboardingTraining;

use App\Security\Voter\Onboarding\DirectOnboardingTrainingVoter;
use Doctrine\ORM\Decorator\EntityManagerDecorator as BaseDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EntityManagerDecorator extends BaseDecorator
{
    public function __construct(
        EntityManagerInterface $wrapped,
        protected Security     $security,
    ) {
        parent::__construct($wrapped);
    }

    public function flush(): void
    {
        if (!$this->isTraining()) {
            parent::flush();
        }
    }

    protected function isTraining(): bool
    {
        return $this->security->isGranted(DirectOnboardingTrainingVoter::DIRECT_ONBOARDING_TRAINING);
    }
}
