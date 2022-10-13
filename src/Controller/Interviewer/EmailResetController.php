<?php

namespace App\Controller\Interviewer;

use App\Entity\DiaryKeeper;
use App\Form\Auth\ChangeEmailType;
use App\Utility\AccountCreationHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailResetController extends AbstractController
{
    /**
     * @Route("/change-email/{diaryKeeper}", name="change_email")
     * @Security("is_granted('EMAIL_CHANGE', diaryKeeper.getUser())")
     */
    public function changeEmail(AccountCreationHelper $accountCreationHelper, Request $request, DiaryKeeper $diaryKeeper): Response
    {
        $user = $diaryKeeper->getUser();
        $household = $diaryKeeper->getHousehold();
        $successUrl = $this->generateUrl('interviewer_dashboard_household', [
            'household' => $household->getId()
        ]);

        $form = $this->createForm(ChangeEmailType::class, null, [
            'save_label' => 'change-email.change-email',
            'cancel_link_href' => $successUrl,
            'user_id' => $user->getId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $cancel = $form->get('button_group')->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return new RedirectResponse($successUrl);
            }

            if ($form->isValid()) {
                $accountCreationHelper->sendAccountCreationEmail($diaryKeeper, $form->get('emailAddress')->getData());

                $this->addSuccessBanner('change-email', 'auth');
                return new RedirectResponse($successUrl);
            }
        }

        return $this->render('interviewer/reset_email.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'action' => 'change-email',

            'areaPeriod' => $household->getAreaPeriod(),
            'diaryKeeper' => $diaryKeeper,
            'household' => $household,
        ]);
    }
}