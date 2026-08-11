<?php

namespace App\Twig;

use App\Entity\DiaryKeeper;
use App\Repository\UserRepository;
use App\Security\ImpersonatorAuthorizationChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SecurityExtension extends AbstractExtension
{
    public function __construct(protected ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker, protected TokenStorageInterface $tokenStorage, protected UserRepository $userRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('impersonator', $this->impersonator(...)),
            new TwigFunction('is_impersonator_granted', $this->isImpersonatorGranted(...)),
        ];
    }

    public function impersonator(): ?UserInterface {
        $token = $this->tokenStorage->getToken();

        if (!$token instanceof SwitchUserToken) {
            return null;
        }

        $impersonator = $token->getOriginalToken()->getUser();
        return $this->userRepository->loadUserByIdentifier($impersonator->getUserIdentifier());
    }

    public function isImpersonatorGranted($attribute, $subject = null): bool {
        return $this->impersonatorAuthorizationChecker->isGranted($attribute, $subject);
    }
}