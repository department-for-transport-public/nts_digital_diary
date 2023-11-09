<?php

namespace App\Security\Voter\Interviewer;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class HouseholdVoter extends Voter
{
    public const SUBMIT = 'SUBMIT_HOUSEHOLD';
    public const VIEW = 'VIEW_HOUSEHOLD';

    public function __construct(protected Security $security) {}

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::SUBMIT, self::VIEW])
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

        if (!$user instanceof User || !$this->security->isGranted('ROLE_INTERVIEWER')) {
            return false;
        }

        return match($attribute) {
            self::SUBMIT =>
                $subject->getAreaPeriod()->getInterviewers()->contains($user->getInterviewer())
                && !$subject->getIsSubmitted()
                && $subject->getState() === DiaryKeeper::STATE_APPROVED,
            self::VIEW =>
                $subject->getIsOnboardingComplete()
                && $this->security->isGranted(AreaPeriodVoter::IS_SUBSCRIBED, $subject->getAreaPeriod()),

            default => false
        };
    }
}
