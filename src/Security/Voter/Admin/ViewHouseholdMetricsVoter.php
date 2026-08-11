<?php

namespace App\Security\Voter\Admin;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ViewHouseholdMetricsVoter extends Voter
{
    public const string ADMIN_VIEW_HOUSEHOLD_METRICS = 'ADMIN_VIEW_HOUSEHOLD_METRICS';

    public function __construct(protected AccessDecisionManagerInterface $accessDecisionManager)
    {}

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute == self::ADMIN_VIEW_HOUSEHOLD_METRICS && $subject === null;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $neededRole = 'ROLE_SUPER_ADMIN';

        return $this->accessDecisionManager->decide($token, [$neededRole]);
    }
}