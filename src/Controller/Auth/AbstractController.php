<?php

namespace App\Controller\Auth;

use App\Controller\AbstractController as RootAbstractController;
use App\Entity\User;
use App\Exception\RedirectResponseException;
use App\Repository\UserRepository;
use App\Security\Voter\UserValidForLoginVoter;
use App\Utility\Security\UrlSigner;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class AbstractController extends RootAbstractController
{
    public function __construct(
        protected EntityManagerInterface   $entityManager,
        protected RequestStack             $requestStack,
        protected UrlSigner                $urlSigner,
        protected UserRepository           $userRepository,
        protected TokenStorageInterface    $tokenStorage,
        protected EventDispatcherInterface $eventDispatcher,
    ) {}

    protected function fetchUserAndCacheCriteria(
        string $sessionKey,
        array $findCriteria,
        array $extraData = [],
        bool $userMustBeValidForLogin = true,
        ?LimiterInterface $signedUrlReuseLimiter = null,
    ): User
    {
        $cachedCriteria = $this->getCriteriaFromSession($sessionKey);
        if (!$cachedCriteria || $cachedCriteria !== $findCriteria) {
            // On the first request we check the signature is valid, and store the criteria
            //
            // On subsequent requests, we just check the criteria matches what we were previously told
            // (i.e. because we won't have the signature at this point)

            $url = $this->requestStack->getCurrentRequest()->getRequestUri();

            if (!$this->urlSigner->isValid($url, rateLimiter: $signedUrlReuseLimiter)) {
                $this->redirectToLoginWithInvalidLinkMessage();
            }

            $cachedCriteria = $findCriteria;
            $this->setSessionData($sessionKey, $cachedCriteria, $extraData);
        }

        $user = $this->userRepository->findOneBy($cachedCriteria);

        if (!$user ||
            (!$this->isGranted(UserValidForLoginVoter::USER_VALID_FOR_LOGIN, $user) && $userMustBeValidForLogin)
        ) {
            $this->redirectToLoginWithInvalidLinkMessage();
        }

        return $user;
    }

    protected function redirectToLoginWithInvalidLinkMessage(): never
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
        $this->requestStack->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $user->getUserIdentifier());
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

    protected function handlePreAuth(string $translationPrefix = 'pre-auth'): ?Response
    {
        $form = $this->createForm(FormType::class, [], [
            'help' => "{$translationPrefix}.help",
            'translation_domain' => 'auth',
        ])
            ->add('continue', ButtonType::class, [
                'label' => 'actions.continue',
                'translation_domain' => 'messages',
            ]);

        return $this->render("auth/pre-auth.html.twig", [
            'form' => $form,
            'translation_prefix' => $translationPrefix,
        ]);
    }
}