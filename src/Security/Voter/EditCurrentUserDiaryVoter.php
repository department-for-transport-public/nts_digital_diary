<?php

namespace App\Security\Voter;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use App\Security\ImpersonatorAuthorizationChecker;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditCurrentUserDiaryVoter extends Voter
{
    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker, private readonly ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        $supportedAttributes = [
            EditDiaryVoter::EDIT_DIARY,
        ];

        return in_array($attribute, $supportedAttributes) && $subject === null;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($attribute !== EditDiaryVoter::EDIT_DIARY) {
            return false;
        }

        if (!($diaryKeeper = $user->getDiaryKeeper()) instanceof DiaryKeeper) {
            return false;
        }

        if ($token instanceof SwitchUserToken) {
            return $this->impersonatorAuthorizationChecker->isGranted(EditDiaryVoter::EDIT_DIARY, $diaryKeeper);
        }
        return $this->authorizationChecker->isGranted(EditDiaryVoter::EDIT_DIARY, $diaryKeeper);
    }
}