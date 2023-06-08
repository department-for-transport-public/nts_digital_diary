<?php

namespace App\Security\Voter\Interviewer;

use App\Entity\InterviewerTrainingRecord;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TrainingModuleVoter extends Voter
{
    public const CAN_START = 'CAN_START';
    public const CAN_COMPLETE = 'CAN_COMPLETE';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::CAN_START, self::CAN_COMPLETE])
            && $subject instanceof InterviewerTrainingRecord;
    }

    /**
     * @param InterviewerTrainingRecord $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return match($attribute) {
            self::CAN_START => is_null($subject->getStartedAt()),
            self::CAN_COMPLETE => !is_null($subject->getStartedAt()) && is_null($subject->getCompletedAt()),

            default => false
        };
    }
}