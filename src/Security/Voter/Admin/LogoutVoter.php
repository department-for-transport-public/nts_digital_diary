<?php

namespace App\Security\Voter\Admin;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LogoutVoter extends Voter
{
    public const string ADMIN_LOGOUT = 'ADMIN_LOGOUT';

    public function __construct(protected AccessDecisionManagerInterface $accessDecisionManager)
    {}

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [
             self::ADMIN_LOGOUT,
        ]) && $subject === null;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $neededRole = 'ROLE_CAN_LOGOUT';

        return $this->accessDecisionManager->decide($token, [$neededRole]);
    }
}