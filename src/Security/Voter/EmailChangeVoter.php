<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EmailChangeVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute == 'EMAIL_CHANGE'
            && $subject instanceof User
            && $subject->hasRole('ROLE_DIARY_KEEPER');
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$subject instanceof User || !$user instanceof User || !$user->hasRole('ROLE_INTERVIEWER')) {
            return false;
        }

        $diaryKeeper = $subject->getDiaryKeeper();
        if (!$diaryKeeper || !$subject->isValidForLogin() || $subject->getPassword() !== null) {
            return false;
        }

        return true;
    }
}
