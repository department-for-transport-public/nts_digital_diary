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
    protected ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker;
    protected TokenStorageInterface $tokenStorage;
    protected UserRepository $userRepository;

    public function __construct(ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker, TokenStorageInterface $tokenStorage, UserRepository $userRepository)
    {
        $this->impersonatorAuthorizationChecker = $impersonatorAuthorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('diary_keeper_display_name', [$this, 'getDiaryKeeperDisplayName'])
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('impersonator', [$this, 'impersonator']),
            new TwigFunction('is_impersonator_granted', [$this, 'isImpersonatorGranted']),
        ];
    }

    public function getDiaryKeeperDisplayName(DiaryKeeper $diaryKeeper): string
    {
        return $this->impersonator() || $this->tokenStorage->getToken()->getUser() !== $diaryKeeper->getUser()
            ? $diaryKeeper->getName()
            : 'My';
    }

    public function impersonator(): ?UserInterface {
        $token = $this->tokenStorage->getToken();

        if (!$token instanceof SwitchUserToken) {
            return null;
        }

        $impersonator = $token->getOriginalToken()->getUser();
        return $this->userRepository->findOneBy(['username' => $impersonator->getUserIdentifier()]);
    }

    public function isImpersonatorGranted($attribute, $subject = null): bool {
        return $this->impersonatorAuthorizationChecker->isGranted($attribute, $subject);
    }
}