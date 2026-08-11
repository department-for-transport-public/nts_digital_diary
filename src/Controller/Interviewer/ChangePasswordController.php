<?php

namespace App\Controller\Interviewer;

use App\Form\ChangePasswordType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChangePasswordController extends AbstractController
{
    #[Route(path: '/change-password', name: 'change_password')]
    public function changePassword(Request $request): Response
    {
        $interviewer = $this->getInterviewer();
        $user = $interviewer->getUser();

        $dashboardUrl = $this->generateUrl('interviewer_dashboard');

        $form = $this->createForm(ChangePasswordType::class, $user, [
            'require_current_password' => true,
            'save_label' => 'actions.change-password',
            'cancel_link_href' => $dashboardUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->security->login($user, 'form_login', 'main');

            $this->addSuccessBanner('change-password', 'interviewer');
            return new RedirectResponse($dashboardUrl);
        }

        return $this->render('interviewer/change_password.html.twig', [
            'form' => $form,
        ]);
    }
}
