<?php

namespace App\FormWizard\OnBoarding;

use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use App\Entity\DiaryKeeper;
use App\Entity\Household;

class DirectOnboardingState extends AbstractFormWizardState
{
    protected ?Household $subject = null;

    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_DIARY_KEEPER_LOGINS = 'diary-keeper-logins';
    public const STATE_DIARY_KEEPER_IDENTITY = 'diary-keeper-identity';
    public const STATE_REVIEW = 'review';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_INTRO_TO_DK_MEDIA = "intro-to-diary-keeper-media";
    public const TRANSITION_DK_LOGINS_TO_DK_IDENTITY = "diary-keeper-logins-to-dk-identity";
    public const TRANSITION_DK_IDENTITY_TO_NEXT_DK_IDENTITY = "diary-keeper-identity-to-dk-identity";
    public const TRANSITION_DK_IDENTITY_TO_REVIEW = "diary-keeper-identity-to-review";
    public const TRANSITION_REVIEW_TO_FINISH = "review-to-finish";


    public function getSubject(): ?Household
    {
        return $this->subject;
    }

    public function setSubject($subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getDiaryKeeperNumber(): ?int
    {
        return $this->getPlaceParameter('diaryKeeperNumber', null);
    }

    public function getNextDiaryKeeperNumber(): ?int
    {
        $currentIndex = $this->subject->getDiaryKeepers()->indexOf($this->getContextDiaryKeeper());
        return $this->getDiaryKeeperNumber()
            ? $this->subject->getDiaryKeepers()->get($currentIndex + 1)?->getNumber()
            : $this->subject->getDiaryKeepers()->first()->getNumber();
    }

    public function getContextDiaryKeeper(): ?DiaryKeeper
    {
        return $this->getDiaryKeeperNumber()
            ? $this->subject->getDiaryKeeperByNumber($this->getDiaryKeeperNumber())
            : null;
    }

    public function getPlaceRouteParameters($place = null): array
    {
        $place ??= $this->place;
        return [
            'place' => $place,
            'diaryKeeperNumber' => $place?->getContextValue('diaryKeeperNumber'),
        ];
    }

    public function getLastDiaryKeeperNumber(): ?int
    {
        return $this->subject->getDiaryKeepers()->last()->getNumber();
    }
}