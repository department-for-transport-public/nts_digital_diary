<?php

namespace App\Security\Voter\Admin;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DataExportVoter extends Voter
{
    public const string ADMIN_DATA_EXPORT = 'ADMIN_DATA_EXPORT';

    public function __construct(protected AccessDecisionManagerInterface $accessDecisionManager)
    {}

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [
             self::ADMIN_DATA_EXPORT,
        ]) && $subject === null;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $neededRole = 'ROLE_DATA_EXPORTER';

        return $this->accessDecisionManager->decide($token, [$neededRole]);
    }
}