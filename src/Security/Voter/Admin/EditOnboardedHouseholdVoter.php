<?php

namespace App\Security\Voter\Admin;

use App\Entity\Household;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditOnboardedHouseholdVoter extends Voter
{
    public const EDIT_ONBOARDED_HOUSEHOLD = 'EDIT_ONBOARDED_HOUSEHOLD';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::EDIT_ONBOARDED_HOUSEHOLD
            && $subject instanceof Household;
    }

    /**
     * @param Household $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if ($subject->getState() !== Household::STATE_NEW) {
            return false;
        }

        if ($subject->getDiaryWeekStartDate() <= new \DateTime('12am today')) {
            return false;
        }

        return true;
    }
}