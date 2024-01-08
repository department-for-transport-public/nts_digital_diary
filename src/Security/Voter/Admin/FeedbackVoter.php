<?php

namespace App\Security\Voter\Admin;

use App\Entity\Feedback\Message;
use App\Security\GoogleIap\AdminRoleResolver;
use App\Security\GoogleIap\IapUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FeedbackVoter extends Voter
{
    public const ADMIN_FEEDBACK_ASSIGN = 'ADMIN_FEEDBACK_ASSIGN';
    public const ADMIN_FEEDBACK_VIEW = 'ADMIN_FEEDBACK_VIEW';
    public const ADMIN_FEEDBACK_VIEW_MESSAGE = 'ADMIN_FEEDBACK_VIEW_MESSAGE';

    public function __construct(
        protected AdminRoleResolver $adminRoleResolver,
        protected AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        return
            ($attribute === self::ADMIN_FEEDBACK_ASSIGN && $subject === null) ||
            ($attribute === self::ADMIN_FEEDBACK_VIEW_MESSAGE && $subject instanceof Message) ||
            ($attribute === self::ADMIN_FEEDBACK_VIEW && $subject === null);
    }

    /**
     * @param Message $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        $isFeedbackAssigner = $this->accessDecisionManager->decide($token, ['ROLE_FEEDBACK_ASSIGNER']);
        $isFeedbackViewer = $this->accessDecisionManager->decide($token, ['ROLE_FEEDBACK_VIEWER']);

        if (!$user instanceof IapUser) {
            return false;
        }

        // Assign page
        if ($attribute === self::ADMIN_FEEDBACK_ASSIGN) {
            return $isFeedbackAssigner;
        }

        // List page
        if ($attribute === self::ADMIN_FEEDBACK_VIEW) {
            // Disabled: only feedback assigners will be able to view feedback via the admin panel
            // return $isFeedbackViewer;
            return $isFeedbackAssigner;
        }

        // View message page
        if ($attribute === self::ADMIN_FEEDBACK_VIEW_MESSAGE) {
            if ($isFeedbackAssigner) {
                // Assigners can view all messages
                return true;
            } else if ($isFeedbackViewer) {
//                // For viewers, their user's domain must match the domain that the message has been assigned to
//                $userDomain = $user->getDomain();
//                $messageAssignedToDomain = $this->adminRoleResolver->getAssignee($subject->getAssignedTo() ?? '')->getDomain();
//
//                return $messageAssignedToDomain === $userDomain;
                return false;
            }
        }

        return false;
    }
}
