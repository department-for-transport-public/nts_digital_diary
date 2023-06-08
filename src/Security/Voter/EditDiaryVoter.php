<?php

namespace App\Security\Voter;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use App\Security\ImpersonatorAuthorizationChecker;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class EditDiaryVoter extends Voter
{
    public const EDIT_DIARY = 'EDIT_DIARY';

    protected function supports(string $attribute, $subject): bool
    {
        $supportedAttributes = [
            self::EDIT_DIARY,
        ];

        return in_array($attribute, $supportedAttributes) && $subject instanceof DiaryKeeper;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($attribute !== self::EDIT_DIARY) {
            return false;
        }

        if (!($subject instanceof DiaryKeeper)) {
            return false;
        }

        return match($subject->getDiaryState()) {
            // DiaryKeeper and Interviewers can edit
            DiaryKeeper::STATE_IN_PROGRESS,
            DiaryKeeper::STATE_NEW => true,

            // Only interviewers can edit
            DiaryKeeper::STATE_COMPLETED => in_array(User::ROLE_INTERVIEWER, $token->getRoleNames()),

            // No one can edit
            // DiaryKeeper::STATE_APPROVED
            // DiaryKeeper::STATE_DISCARDED
            default => false,
        };
    }
}