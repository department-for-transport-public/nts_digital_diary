<?php

namespace App\Security\OneTimePassword;

use App\Entity\AreaPeriod;
use App\Entity\Household;
use App\Entity\Interviewer;
use Symfony\Component\Security\Core\User\UserInterface;

class InMemoryOtpUser implements UserInterface, OtpUserInterface
{
    public const string ROLE_ON_BOARDING_TRAINING = 'ROLE_ON_BOARDING_TRAINING';
    public const string ROLE_DIRECT_ON_BOARDING_TRAINING = 'ROLE_DIRECT_ON_BOARDING_TRAINING';
    public const string ROLE_MANUAL_ON_BOARDING_TRAINING = 'ROLE_MANUAL_ON_BOARDING_TRAINING';

    public function __construct(private ?string $userIdentifier, private ?Interviewer $interviewer, private ?Household $household = null)
    {
    }

    public function getRoles(): array
    {
        return [
            'ROLE_ON_BOARDING',
            self::ROLE_ON_BOARDING_TRAINING,
            match($this->getUserIdentifier()) {
                DirectOnboardingTrainingUserProvider::USER_IDENTIFIER => self::ROLE_DIRECT_ON_BOARDING_TRAINING,
                InterviewerLinkedTrainingUserProvider::USER_IDENTIFIER => self::ROLE_MANUAL_ON_BOARDING_TRAINING,
            },
        ];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;
        return $this;
    }

    public function getHousehold(): ?Household
    {
        return $this->household ?? null;
    }

    public function setHousehold(?Household $household): self
    {
        $this->household = $household;
        return $this;
    }

    public function getAreaPeriod(): ?AreaPeriod
    {
        return $this->interviewer?->getTrainingAreaPeriodBySerial(AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL);
    }

    public function getInterviewer(): ?Interviewer
    {
        return $this->interviewer;
    }

    public function setInterviewer(?Interviewer $interviewer): self
    {
        $this->interviewer = $interviewer;
        return $this;
    }
}
