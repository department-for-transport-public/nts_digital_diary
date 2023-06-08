<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Entity\UserPersonInterface;
use App\Form\Auth\ChangePasswordType;
use App\Form\Auth\ForgottenPasswordType;
use App\Messenger\AlphagovNotify\Email;
use App\Security\Voter\UserValidForLoginVoter;
use App\Utility\AlphagovNotify\Reference;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PasswordResetController extends AbstractController
{
    /**
     * @Route("/forgotten-password", name="forgotten_password")
     */
    public function forgottenPassword(Request $request, MessageBusInterface $messageBus, string $secret): Response
    {
        $this->redirectToDashboardIfAppropriate();

        $form = $this->createForm(ForgottenPasswordType::class, null, [
            'cancel_link_href' => $this->generateUrl('app_home'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cancel = $form->get('button_group')->get('cancel');

            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return new RedirectResponse($this->generateUrl('app_home'));
            }

            $emailAddress = $form->get('emailAddress')->getData();

            /** @var ?User $user */
            $user = $this->userRepository->findOneBy(['username' => $emailAddress]);

            $diaryKeeper = $user?->getDiaryKeeper();
            $interviewer = $user?->getInterviewer();
            /** @var UserPersonInterface $person */
            $person = $diaryKeeper ?? $interviewer ?? null;

            if ($person && $user && $this->isGranted(UserValidForLoginVoter::USER_VALID_FOR_LOGIN, $user)) {
                $passwordResetCode = substr(hash_hmac('sha256', $user->getId() . rand(), $secret), 0, 16);
                $user->setPasswordResetCode($passwordResetCode);
                $this->entityManager->flush();

                $url = $this->generateUrl('auth_password_reset',
                    [
                        'userId' => $user->getId(),
                        'code' => $user->getPasswordResetCode(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $signedUrl = $this->urlSigner->sign($url, Reference::FORGOTTEN_PASSWORD_LINK_EXPIRY);

                $messageBus->dispatch(new Email(
                    Reference::FORGOTTEN_PASSWORD_EVENT,
                    get_class($person),
                    $person->getId(),
                    $person->getUser()->getUserIdentifier(),
                    Reference::FORGOTTEN_PASSWORD_TEMPLATE_ID,
                    ['name' => $person->getName(), 'url' => $signedUrl],
                ));
            }

            $this->addSuccessBanner("forgotten-password", "auth");
            return new RedirectResponse($this->generateUrl('app_home'));
        }

        return $this->render('auth/forgotten_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/password-reset/{userId}/{code}", name="password_reset")
     */
    public function passwordReset(Request $request, ?UserInterface $currentUser, string $userId, string $code): Response
    {
        $sessionKey = 'account_password_reset';
        $user = $this->fetchUserAndCacheCriteria($sessionKey, ['id' => $userId, 'passwordResetCode' => $code]);
        if ($currentUser && $currentUser->getUserIdentifier() !== $user->getUserIdentifier()) {
            return $this->render('auth/change_password_disallowed.html.twig', [
                'user' => $user,
                'currentUser' => $currentUser,
            ]);
        }

        $successUrl = $this->generateUrl('app_login');

        $form = $this->createForm(ChangePasswordType::class, $user, [
            'save_label' => 'change-password.change-password',
            'cancel_link_href' => $successUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->clearSessionAndSetLastUser($user, $sessionKey);
            $this->addSuccessBanner("change-password", "auth");
            return new RedirectResponse($successUrl);
        }

        return $this->render('auth/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}