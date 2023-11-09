<?php

namespace App\Controller\Auth;

use App\Form\Auth\ChangePasswordType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SetupController extends AbstractController
{
    /**
     * @Route("/account-setup/{userId}", name="account_setup")
     */
    public function setup(string $userId, Request $request): Response
    {
        $sessionKey = 'account_setup_user';
        $user = $this->fetchUserAndCacheCriteria($sessionKey,
            ['id' => $userId],
            [$request->query->get('email')],
            false
        );

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
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}