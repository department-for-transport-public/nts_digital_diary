<?php

namespace App\Security\Voter\Admin;

use App\Entity\Feedback\Message;
use App\Security\GoogleIap\AdminRoleResolver;
use App\Security\GoogleIap\IapUser;
use App\Security\OneTimePassword\OtpUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FeedbackMessageVoter extends Voter
{
    public const MESSAGE_VIEW = 'MESSAGE_VIEW';

    public function __construct(protected AdminRoleResolver $adminRoleResolver) {}

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::MESSAGE_VIEW])
            && $subject instanceof Message;
    }

    /**
     * @param Message $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof IapUser) {
            return false;
        }

        if ($attribute === self::MESSAGE_VIEW) {
            return $this->adminRoleResolver->getAssignee($subject->getAssignedTo() ?? '')->getDomain() === $user->getDomain();
        }

        return false;
    }
}
