<?php

namespace App\Security\Voter;

use App\Entity\Interviewer;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DiaryKeeperImpersonationVoter extends Voter
{
    /**
     * @param User $subject
     */
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === 'CAN_IMPERSONATE_USER'
            && $subject instanceof User
            && $subject->hasRole('ROLE_DIARY_KEEPER');
    }

    /**
     * @param User $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token instanceof SwitchUserToken
            ? $token->getOriginalToken()->getUser()
            : $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->hasRole('ROLE_DIARY_KEEPER')) {
            return $user->getDiaryKeeper()->isActingAsProxyFor($subject->getDiaryKeeper());
        }

        if ($user->hasRole('ROLE_INTERVIEWER')) {
            $household = $subject->getDiaryKeeper()->getHousehold();
            $interviewers = $household->getAreaPeriod()->getInterviewers();
            $interviewerUserIds = array_map(fn(Interviewer $i) => $i->getUser()->getId(), $interviewers->toArray());
            return $household->getIsOnboardingComplete() && in_array($user->getId(), $interviewerUserIds);
        }

        return false;
    }
}