<?php

namespace App\Controller\Auth;

use App\Controller\AbstractController as RootAbstractController;
use App\Entity\User;
use App\Exception\RedirectResponseException;
use App\Repository\UserRepository;
use App\Utility\UrlSigner;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AbstractController extends RootAbstractController
{
    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;
    protected UrlSigner $urlSigner;
    protected UserRepository $userRepository;
    private TokenStorageInterface $tokenStorage;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack, UrlSigner $urlSigner, UserRepository $userRepository, TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->urlSigner = $urlSigner;
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function fetchUserAndCacheCriteria(string $sessionKey, array $findCriteria, array $extraData = []): User
    {
        $cachedCriteria = $this->getCriteriaFromSession($sessionKey);
        if (!$cachedCriteria || $cachedCriteria !== $findCriteria) {
            // On the first request we check the signature is valid, and store the criteria
            //
            // On subsequent requests, we just check the criteria matches what we were previously told
            // (i.e. because we won't have the signature at this point)

            $url = $this->requestStack->getCurrentRequest()->getRequestUri();

            if (!$this->urlSigner->isValid($url)) {
                $this->redirectToLoginWithInvalidLinkMessage();
            }

            $cachedCriteria = $findCriteria;
            $this->setSessionData($sessionKey, $cachedCriteria, $extraData);
        }

        $user = $this->userRepository->findOneBy($cachedCriteria);

        if (!$user || !$user->isValidForLogin()) {
            $this->redirectToLoginWithInvalidLinkMessage();
        }

        return $user;
    }

    protected function redirectToLoginWithInvalidLinkMessage()
    {
        $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
            'link-invalid.flash-notification.title',
            'link-invalid.flash-notification.heading',
            'link-invalid.flash-notification.content',
            [], [], 'auth'
        ));
        throw new RedirectResponseException(new RedirectResponse($this->generateUrl('app_login')));
    }

    protected function clearSessionAndSetLastUser(User $user, string $sessionKey): void
    {
        $this->clearSessionData($sessionKey);
        $this->requestStack->getSession()->set(Security::LAST_USERNAME, $user->getUserIdentifier());
    }

    private function setSessionData(string $sessionKey, array $criteria, array $extraData): void
    {
        $session = $this->requestStack->getSession();
        $session->set("{$sessionKey}-criteria", $criteria);
        $session->set("{$sessionKey}-extraData", $extraData);
    }

    private function clearSessionData(string $sessionKey): void
    {
        $session = $this->requestStack->getSession();
        $session->remove("{$sessionKey}-criteria");
        $session->remove("{$sessionKey}-extraData");
    }

    protected function getCriteriaFromSession(string $sessionKey)
    {
        return $this->requestStack->getSession()->get("{$sessionKey}-criteria");
    }

    protected function getExtraDataFromSession(string $sessionKey)
    {
        return $this->requestStack->getSession()->get("{$sessionKey}-extraData");
    }

    protected function authenticateUser(User $user)
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        $this->eventDispatcher->dispatch(
            new InteractiveLoginEvent($this->requestStack->getCurrentRequest(), $token),
            "security.interactive_login"
        );
    }
}