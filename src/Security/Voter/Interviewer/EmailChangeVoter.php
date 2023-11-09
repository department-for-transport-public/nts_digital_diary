<?php

namespace App\Security\Voter\Interviewer;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use App\Security\Voter\UserValidForLoginVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class EmailChangeVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute == 'EMAIL_CHANGE'
            && $subject instanceof User
            && $subject->hasRole('ROLE_DIARY_KEEPER');
    }

    /**
     * @param User $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$user->hasRole('ROLE_INTERVIEWER')) {
            return false;
        }

        $diaryKeeper = $subject->getDiaryKeeper();
        if (!$diaryKeeper
            || $subject->getPassword() !== null
            || !in_array($diaryKeeper->getDiaryState(), [DiaryKeeper::STATE_NEW, DiaryKeeper::STATE_IN_PROGRESS])
            || $subject->getTrainingInterviewer() !== null
        ) {
            return false;
        }

        return true;
    }
}
