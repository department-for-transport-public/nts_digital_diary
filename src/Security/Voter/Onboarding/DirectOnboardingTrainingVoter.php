<?php

namespace App\Security\Voter\Onboarding;

use App\Security\OneTimePassword\DirectOnboardingTrainingUserProvider;
use App\Security\OneTimePassword\InMemoryOtpUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DirectOnboardingTrainingVoter extends Voter
{
    public const DIRECT_ONBOARDING_TRAINING = 'DIRECT_ONBOARDING_TRAINING';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::DIRECT_ONBOARDING_TRAINING]);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof InMemoryOtpUser) {
            return false;
        }

        if ($attribute === self::DIRECT_ONBOARDING_TRAINING) {
            return $user->getUserIdentifier() === DirectOnboardingTrainingUserProvider::USER_IDENTIFIER;
        }

        return false;
    }
}