<?php

namespace App\Security\Voter\Interviewer;

use App\Entity\AreaPeriod;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class AreaPeriodVoter extends Voter
{
    public const IS_SUBSCRIBED = "INTERVIEWER_IS_SUBSCRIBED";

    public function __construct(protected Security $security) {}

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::IS_SUBSCRIBED
            && $subject instanceof AreaPeriod;
    }

    /**
     * @param $subject AreaPeriod
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$this->security->isGranted('ROLE_INTERVIEWER')) {
            return false;
        }

        $interviewer = $user->getInterviewer();

        return match($attribute) {
            self::IS_SUBSCRIBED =>
                $subject->getTrainingInterviewer() === $interviewer
                || $subject->getInterviewers()->contains($interviewer),

            default => false
        };
    }
}