<?php

namespace App\Doctrine\ORM\Decorator\DirectOnboardingTraining;

use App\Entity\DiaryKeeper;
use App\Repository\DiaryKeeperRepository;
use App\Security\Voter\Onboarding\DirectOnboardingTrainingVoter;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method null loadUserByIdentifier(string $identifier)
 */
class DiaryKeeperRepositoryDecorator extends DiaryKeeperRepository
{
    public function getOnBoardingProxyChoices(DiaryKeeper $diaryKeeperForExclusion): array
    {
        if ($this->isTraining()) {
            $diaryKeepers = clone $diaryKeeperForExclusion->getHousehold()->getDiaryKeepers();
            $diaryKeepers->removeElement($diaryKeeperForExclusion);
            return $diaryKeepers->toArray();
        }
        return parent::getOnBoardingProxyChoices($diaryKeeperForExclusion);
    }

    protected function isTraining(): bool
    {
        return $this->security->isGranted(DirectOnboardingTrainingVoter::DIRECT_ONBOARDING_TRAINING);
    }
}