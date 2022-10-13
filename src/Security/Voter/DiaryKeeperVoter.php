<?php

namespace App\Security\Voter;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DiaryKeeperVoter extends Voter
{
    public const DIARY_KEEPER_WITH_APPROVED_DIARY = 'DIARY_KEEPER_WITH_APPROVED_DIARY';

    protected function supports(string $attribute, $subject): bool
    {
        $supportedAttributes = [
            self::DIARY_KEEPER_WITH_APPROVED_DIARY,
        ];

        return in_array($attribute, $supportedAttributes);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $isInterviewerImpersonatingDiaryKeeper = false;
        if ($token instanceof SwitchUserToken) {
            $originalUser = $token->getOriginalToken()->getUser();

            if ($originalUser instanceof User) {
                $isInterviewerImpersonatingDiaryKeeper = $originalUser->hasRole(User::ROLE_INTERVIEWER);
            }
        }

        if ($attribute === self::DIARY_KEEPER_WITH_APPROVED_DIARY) {
            $hasDiaryKeeperRole = $user->hasRole(User::ROLE_DIARY_KEEPER);
            $diaryKeeper = $user->getDiaryKeeper();
            $householdIsSubmitted = $diaryKeeper->getHousehold()->getIsSubmitted();

            $isDiaryKeeperWithApprovedDiary =
                !$isInterviewerImpersonatingDiaryKeeper &&
                $hasDiaryKeeperRole &&
                (
                    $diaryKeeper->getDiaryState() === DiaryKeeper::STATE_APPROVED ||
                    $householdIsSubmitted
                );

            $isInterviewerImpersonatingWithSubmittedHousehold =
                $isInterviewerImpersonatingDiaryKeeper &&
                $hasDiaryKeeperRole &&
                $householdIsSubmitted;

            return $isDiaryKeeperWithApprovedDiary || $isInterviewerImpersonatingWithSubmittedHousehold;
        }

        return false;
    }
}