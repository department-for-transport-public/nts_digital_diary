<?php

namespace App\Security\Voter\Admin;

use App\Entity\Household;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

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
     * @param Household $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return
            $this->security->isGranted('ROLE_MAINTENANCE')
            && in_array($subject->getState(), [Household::STATE_NEW, Household::STATE_IN_PROGRESS]);
    }
}
