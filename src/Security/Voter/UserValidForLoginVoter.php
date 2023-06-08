<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserValidForLoginVoter extends Voter
{
    const USER_VALID_FOR_LOGIN = 'USER_VALID_FOR_LOGIN';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::USER_VALID_FOR_LOGIN
            && $subject instanceof User;
    }

    /**
     * @param User $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject->hasIdentifierForLogin()) {
            return false;
        }

        if ($subject->getInterviewer()) {
            return true;
        }

        return $subject->getDiaryKeeper()?->getHousehold()->getIsOnboardingComplete();
    }
}