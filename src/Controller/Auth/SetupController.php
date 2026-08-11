<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Exception\RedirectResponseException;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use App\Utility\Security\AccountCreationHelper;
use App\Utility\Security\UrlSigner;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

class SetupController extends AbstractController
{
    private const string SESSION_KEY = 'account_setup_user';

    public function __construct(
        EntityManagerInterface          $entityManager, RequestStack $requestStack, UrlSigner $urlSigner,
        UserRepository                  $userRepository, TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher,
        protected AccountCreationHelper $accountCreationHelper,
        protected RateLimiterFactory    $accountCreationLinkExpiredLimiter,
        private readonly Security       $security,
        protected RateLimiterFactory    $accountActivationLinkReuseLimiter,
    ) {
        parent::__construct($entityManager, $requestStack, $urlSigner, $userRepository, $tokenStorage, $eventDispatcher);
    }

     #[Route("/account-setup/{userId}", name: "account_setup")]
    public function setup(UrlSigner $urlSigner, string $userId, Request $request): Response
    {
        if ($request->getMethod() === Request::METHOD_GET) {
            return $this->handlePreAuth();
        }

        $emailParameter = $request->query->get(AccountCreationHelper::EMAIL_OVERRIDE_PARAM);

        // If the visitor is already authenticated as this user (e.g. returning via the email link with a
        // live remember-me cookie), don't spend a single-use reuse token - they've already proven who they
        // are. The signature is still validated; only the reuse limiter is skipped.
        $alreadyThisUser = $this->security->getUser()?->getId() === $userId;

        try {
            $user = $this->fetchUserAndCacheCriteria(self::SESSION_KEY,
                ['id' => $userId],
                [$emailParameter],
                false,
                signedUrlReuseLimiter: $alreadyThisUser
                    ? null
                    : $this->accountActivationLinkReuseLimiter->create("account-creation-{$request->getRequestUri()}")
            );
        } catch (RedirectResponseException $redirectResponseException) {
            // A redirect response was thrown, which means the link was invalid
            if (in_array($urlSigner->getLastInvalidReason(), [UrlSigner::REASON_EXPIRED, UrlSigner::REASON_USED_TOO_MANY_TIMES])) {
                // the link was expired/overused
                // send a new email and let them know, but don't redirect
                return $this->creationLinkWasExpired($userId, $emailParameter);
            }
            // it was an invalid link for some other reason, redirect to log in with the original error message
            throw $redirectResponseException;
        }

        $request->getSession()->set(UrlSigner::SOURCE_TRACKING_PARAMETER_NAME, $request->query->get(UrlSigner::SOURCE_TRACKING_PARAMETER_NAME));

        if ($user->getPassword() !== null) {
            return $this->handlePasswordAlreadySet($user);
        }

        $this->handleEmailOverride($user);
        return $this->handleSetPassword($user);
    }

    private function handlePasswordAlreadySet(User $user): Response
    {
        // Is the user already logged in and match?
        if ($this->security->getUser()?->getUserIdentifier() === $user->getUserIdentifier()) {
            return new RedirectResponse($this->generateUrl('app_home'));
        }

        // Do not log in an interviewer
        if ($user->getInterviewer()) {
            return new RedirectResponse($this->generateUrl('app_login'));
        }

        $this->clearSessionAndSetLastUser($user, self::SESSION_KEY);
        return $this->loginAndGetRedirect($user);
    }

    private function handleSetPassword(User $user): Response
    {
        $form = $this->createForm(ChangePasswordType::class, $user, [
            'save_label' => 'actions.create-account',
        ]);

        $form->handleRequest($this->requestStack->getCurrentRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->clearSessionAndSetLastUser($user, self::SESSION_KEY);
            $this->addSuccessBanner("setup", "auth");

            if ($user->getInterviewer()) {
                return new RedirectResponse($this->generateUrl('app_login'));
            }

            return $this->loginAndGetRedirect($user);
        }

        return $this->render('auth/account_setup.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    private function handleEmailOverride(User $user): void
    {
        [$emailOverride] = $this->getExtraDataFromSession(self::SESSION_KEY);
        if ($emailOverride) {
            $user->setUsername($emailOverride);
            $user->setHasPendingUsernameChange(false);

            if ($this->userRepository->canChangeEmailTo($emailOverride, $user->getId())) {
                throw new AccessDeniedHttpException();
            }
        }
    }

    protected function loginAndGetRedirect(UserInterface $user): Response
    {
        return $this->security->login($user, null, 'main', [(new RememberMeBadge())->enable()])
            ?? new RedirectResponse($this->generateUrl('app_login'));
    }

    /**
     * The link in the account creation email was expired. Send the user a new link, and let them know what's happened
     */
    protected function creationLinkWasExpired(string $userId, ?string $emailOverrideParameter): Response
    {
        $this->requestStack->getSession()->getFlashBag()->clear();
        $user = $this->userRepository->find($userId);
        if (!$user || !$user->hasIdentifierForLogin()) {
            $this->redirectToLoginWithInvalidLinkMessage();
        }

        // the link was valid but expired/overused...
        // interviewers must log in with a password, so there's no benefit to resending a link
        if ($user->getInterviewer() && $user->getPassword()) {
            return new RedirectResponse($this->generateUrl('app_login'));
        }

        $emailSent = false;
        if ($user->getPassword() === null) {
            // Only un-activated users benefit from a fresh setup link. Activated users already have a
            // password, so we steer them to log in (see the template) rather than resending a
            // "create your account" email that no longer makes sense for them.
            $limiter = $this->accountCreationLinkExpiredLimiter->create($userId);
            if ($limiter->consume()->isAccepted()) {
                // send the emails
                $emailSent = $this->accountCreationHelper->sendAccountCreationEmail($user->getDiaryKeeper() ?? $user->getInterviewer(), $emailOverrideParameter);
            }
        }

        // render template
        return $this->render('auth/account_setup_link_expired.html.twig', [
            'user' => $user,
            'emailAddress' => $emailOverrideParameter ?? $user->getUserIdentifier(),
            'emailSent' => $emailSent,
        ]);
    }
}