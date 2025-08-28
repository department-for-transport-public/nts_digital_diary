<?php

namespace App\Utility\TravelDiary;

use App\Entity\PreferenceUserInterface;
use App\Entity\UserPreferences;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;

class UserPreferencesHelper
{
    public function __construct(protected Security $security)
    {}

    public function hasUserPreferences(): bool
    {
        return $this->isPreferenceUser($this->getOriginalUser());
    }

    protected function isPreferenceUser(?UserInterface $user): bool
    {
        if (!$user instanceof PreferenceUserInterface) {
            return false;
        }
        return true;
    }

    public function getOriginalUserPreferences(): ?UserPreferences
    {
        $user = $this->getOriginalUser();
        if (!$this->isPreferenceUser($user)) {
            return null;
        }

        return $user->getPreferences();
    }

    protected function getOriginalUser(): ?PreferenceUserInterface
    {
        $token = $this->security->getToken();
        if ($token === null) {
            return null;
        }

        if ($token instanceof SwitchUserToken) {
            $token = $token->getOriginalToken();
        }

        $user = $token->getUser();
        if (!$user instanceof PreferenceUserInterface) {
            return null;
        }

        return $user;
    }
}