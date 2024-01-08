<?php

namespace App\Security\Voter\Admin;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InterviewerVoter extends Voter
{
    const ADMIN_INTERVIEWER_ADD = 'ADMIN_INTERVIEWER_ADD';
    const ADMIN_INTERVIEWER_ALLOCATE_AREAS = 'ADMIN_INTERVIEWER_ALLOCATE_AREAS';
    const ADMIN_INTERVIEWER_DELETE = 'ADMIN_INTERVIEWER_DELETE';
    const ADMIN_INTERVIEWER_VIEW = 'ADMIN_INTERVIEWER_VIEW';

    public function __construct(protected AccessDecisionManagerInterface $accessDecisionManager)
    {}

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [
            self::ADMIN_INTERVIEWER_ADD,
            self::ADMIN_INTERVIEWER_ALLOCATE_AREAS,
            self::ADMIN_INTERVIEWER_DELETE,
             self::ADMIN_INTERVIEWER_VIEW,
        ]) && $subject === null;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $neededRole = match($attribute) {
            self::ADMIN_INTERVIEWER_VIEW => 'ROLE_ADMIN',
            default => 'ROLE_INTERVIEWER_ADMIN',
        };

        return $this->accessDecisionManager->decide($token, [$neededRole]);
    }
}