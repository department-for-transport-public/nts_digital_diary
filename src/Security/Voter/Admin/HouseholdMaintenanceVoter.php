<?php

namespace App\Security\Voter\Admin;

use App\Entity\Feedback\Message;
use App\Entity\Household;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class HouseholdMaintenanceVoter extends Voter
{
    public const HOUSEHOLD_MAINTENANCE_ADD_DIARY_KEEPER = 'HOUSEHOLD_MAINTENANCE_ADD_DIARY_KEEPER';

    public function __construct(protected Security $security)
    {}

    protected function supports(string $attribute, $subject): bool
    {
        return
            $attribute === self::HOUSEHOLD_MAINTENANCE_ADD_DIARY_KEEPER &&
            $subject instanceof Household;
    }

    /**
     * @param Message $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return
            $this->security->isGranted('ROLE_MAINTENANCE') &&
            $subject instanceof Household &&
            !$subject->getIsSubmitted();
    }
}
