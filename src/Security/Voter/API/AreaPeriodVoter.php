<?php

namespace App\Security\Voter\API;

use App\Entity\AreaPeriod;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AreaPeriodVoter extends Voter
{
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        $supportedAttributes = [
            self::DELETE,
        ];

        return in_array($attribute, $supportedAttributes)
            && $subject instanceof AreaPeriod;
    }

    /**
     * @param AreaPeriod $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $subject->getHouseholds()->isEmpty();
    }
}