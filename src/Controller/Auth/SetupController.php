<?php

namespace App\Controller\Auth;

use App\Exception\RedirectResponseException;
use App\Form\Auth\ChangePasswordType;
use App\Repository\UserRepository;
use App\Utility\Security\AccountCreationHelper;
use App\Utility\Security\UrlSigner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SetupController extends AbstractController
{

    public function __construct(
        EntityManagerInterface $entityManager, RequestStack $requestStack, UrlSigner $urlSigner,
        UserRepository $userRepository, TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher,
        protected AccountCreationHelper $accountCreationHelper,
        protected RateLimiterFactory $accountCreationLinkExpiredLimiter,
    ) {
        parent::__construct($entityManager, $requestStack, $urlSigner, $userRepository, $tokenStorage, $eventDispatcher);
    }

     #[Route("/account-setup/{userId}", name: "account_setup")]
    public function setup(UrlSigner $urlSigner, string $userId, Request $request): Response
    {
        $sessionKey = 'account_setup_user';
        $emailParameter = $request->query->get(AccountCreationHelper::EMAIL_OVERRIDE_PARAM);
        try {
            $user = $this->fetchUserAndCacheCriteria($sessionKey,
                ['id' => $userId],
                [$emailParameter],
                false
            );
        } catch (RedirectResponseException $redirectResponseException) {
            // A redirect response was thrown, which means the link was invalid
            if ($urlSigner->getLastInvalidReason() === UrlSigner::REASON_EXPIRED) {
                // the link was expired, send a new email and let them know, but don't redirect
                return $this->creationLinkWasExpired($userId, $emailParameter);
            }
            throw $redirectResponseException;
        }

        if ($user->getPassword() !== null) {
            // account already setup, redirect to login
            return new RedirectResponse($this->generateUrl('app_login'));
        }

        [$emailOverride] = $this->getExtraDataFromSession($sessionKey);
        if ($emailOverride) {
            $user->setUsername($emailOverride);
            $user->setHasPendingUsernameChange(false);

            if ($this->userRepository->canChangeEmailTo($emailOverride, $userId)) {
                throw new AccessDeniedHttpException();
            }
        }

        $form = $this->createForm(ChangePasswordType::class, $user, [
            'save_label' => 'setup.create-account',
        ]);

        $form->handleRequest($this->requestStack->getCurrentRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->clearSessionAndSetLastUser($user, $sessionKey);
            $this->addSuccessBanner("setup", "auth");

            return new RedirectResponse($this->generateUrl('app_login'));
        }

        return $this->render('auth/account_setup.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * The link in the account creation email was expired. Send the user a new link, and let them know what's happened
     */
    protected function creationLinkWasExpired(string $userId, ?string $emailOverrideParameter): Response
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->redirectToLoginWithInvalidLinkMessage();
        }

        $this->requestStack->getSession()->getFlashBag()->clear();

        if ($user->getPassword() !== null) {
            // account already setup, redirect to login
            return new RedirectResponse($this->generateUrl('app_login'));
        }

        // at this point, the user is valid for login, and hasn't already set a password
        $limiter = $this->accountCreationLinkExpiredLimiter->create($userId);
        $rateLimit = $limiter->consume();
        $emailSent = false;
        if ($rateLimit->isAccepted()) {
            // send the emails
            $this->accountCreationHelper->sendAccountCreationEmail($user->getDiaryKeeper() ?? $user->getInterviewer(), $emailOverrideParameter);
            $emailSent = true;
        }

        // render template
        return $this->render('auth/account_setup_link_expired.html.twig', [
            'user' => $user,
            'emailAddress' => $emailOverrideParameter ?? $user->getUserIdentifier(),
            'emailSent' => $emailSent,
        ]);
    }
}