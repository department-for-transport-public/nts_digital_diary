<?php

namespace App\Security\Voter\TravelDiary;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SatisfactionSurveyVoter extends Voter
{
    public const ELIGIBLE_FOR_SATISFACTION_SURVEY = 'ELIGIBLE_FOR_SATISFACTION_SURVEY';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::ELIGIBLE_FOR_SATISFACTION_SURVEY;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // Must be non-proxying
        $user = $token->getUser();
        if ($token instanceof SwitchUserToken || !$user instanceof User) {
            return false;
        }

        // Must be a diaryKeeper user
        $diaryKeeper = $user->getDiaryKeeper();
        if (!$diaryKeeper) {
            return false;
        }

        // Must not have already completed satisfaction survey
        if ($diaryKeeper->getSatisfactionSurvey()) {
            return false;
        }

        // Must have a completed survey
        $diaryState = $diaryKeeper->getDiaryState();
        if (in_array($diaryState, [DiaryKeeper::STATE_NEW, DiaryKeeper::STATE_IN_PROGRESS])) {
            return false;
        }

        return true;
    }
}