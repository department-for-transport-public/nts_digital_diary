<?php

namespace App\Security\Voter\Interviewer;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SubmitHouseholdVoter extends Voter
{
    public const SUBMIT_HOUSEHOLD = 'SUBMIT_HOUSEHOLD';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::SUBMIT_HOUSEHOLD
            && $subject instanceof Household;
    }

    /**
     * @param string $attribute
     * @param Household $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return
            $user->hasRole('ROLE_INTERVIEWER')
            && $subject->getAreaPeriod()->getInterviewers()->contains($user->getInterviewer())
            && !$subject->getIsSubmitted()
            && $subject->getState() === DiaryKeeper::STATE_APPROVED
            ;
    }
}
