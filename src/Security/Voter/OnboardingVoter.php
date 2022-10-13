<?php

namespace App\Security\Voter;

use App\Entity\OtpUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OnboardingVoter extends Voter
{
    public const ONBOARDING_EDIT = 'ONBOARDING_EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::ONBOARDING_EDIT]);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof OtpUser) {
            return false;
        }

        if ($attribute === self::ONBOARDING_EDIT) {
            return is_null($user->getHousehold()) || !$user->getHousehold()->getIsOnboardingComplete();
        }

        return false;
    }
}
