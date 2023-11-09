<?php

namespace App\Security\Voter\Interviewer;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ChangeProxiesVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute == 'CHANGE_PROXIES'
            && $subject instanceof DiaryKeeper;
    }

    /**
     * @param DiaryKeeper $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (
            !$user instanceof User ||
            !$user->hasRole('ROLE_INTERVIEWER') ||
            $subject->isTheOnlyDiaryKeeper()
        ) {
            return false;
        }

        if (!in_array($subject->getDiaryState(), [DiaryKeeper::STATE_NEW, DiaryKeeper::STATE_IN_PROGRESS])) {
            return false;
        }

        return true;
    }
}
