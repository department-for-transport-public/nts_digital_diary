<?php

namespace App\Controller\Interviewer;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use App\Form\Auth\ChangeEmailType;
use App\Utility\Metrics\Events\ChangeDiaryKeeperEmailEvent;
use App\Utility\Metrics\MetricsHelper;
use App\Utility\Security\AccountCreationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class EmailResetController extends AbstractController
{
    /**
     * @Route("/change-email/{diaryKeeper}", name="change_email")
     * @Security("is_granted('EMAIL_CHANGE', diaryKeeper.getUser())")
     */
    public function changeEmail(
        AccountCreationHelper  $accountCreationHelper,
        DiaryKeeper            $diaryKeeper,
        EntityManagerInterface $entityManager,
        MetricsHelper          $metricsHelper,
        Request                $request,
        UserInterface          $interviewerUser,
    ): Response
    {
        $diaryKeeperUser = $diaryKeeper->getUser();
        $household = $diaryKeeper->getHousehold();
        $successUrl = $this->generateUrl('interviewer_dashboard_diary_keeper', [
            'diaryKeeper' => $diaryKeeper->getId()
        ]);

        $form = $this->createForm(ChangeEmailType::class, null, [
            'save_label' => 'change-email.change-email',
            'cancel_link_href' => $successUrl,
            'user_id' => $diaryKeeperUser->getId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $accountCreationHelper->sendAccountCreationEmail($diaryKeeper, $newAddress = $form->get('emailAddress')->getData());
                $diaryKeeperUser->setHasPendingUsernameChange(true);
                $entityManager->flush();

                $this->addSuccessBanner('change-email', 'auth', ['newAddress' => $newAddress]);

                // N.B. Always will be, but lets PHPStorm know...
                if ($interviewerUser instanceof User) {
                    $metricsHelper->log(new ChangeDiaryKeeperEmailEvent($diaryKeeper, $interviewerUser->getInterviewer()));
                }

                return new RedirectResponse($successUrl);
            }
        }

        return $this->render('interviewer/reset_email.html.twig', [
            'form' => $form->createView(),
            'user' => $diaryKeeperUser,
            'action' => 'change-email',

            'areaPeriod' => $household->getAreaPeriod(),
            'diaryKeeper' => $diaryKeeper,
            'household' => $household,
        ]);
    }
}