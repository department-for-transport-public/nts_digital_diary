<?php

namespace App\Controller\Interviewer\HouseholdManagement;

use App\Controller\Interviewer\AbstractController;
use App\Entity\DiaryKeeper;
use App\Entity\User;
use App\Form\Auth\ChangeEmailType;
use App\Utility\Metrics\Events\ChangeDiaryKeeperEmailEvent;
use App\Utility\Metrics\MetricsHelper;
use App\Utility\Security\AccountCreationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class EmailResetController extends AbstractController
{
    #[Route(path: '/change-email/{diaryKeeper}', name: 'change_email')]
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

        // TODO: Try replacing this with #[IsGranted] once we have #[MapEntity] in place...
        $this->denyAccessUnlessGranted('EMAIL_CHANGE', $diaryKeeperUser);

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
                $diaryKeeperUser->setPassword(null);
                $diaryKeeperUser->setHasPendingUsernameChange(true);
                $entityManager->flush();

                $this->addSuccessBanner('change-email', 'auth', ['newAddress' => $newAddress]);

                // N.B. Always will be, but lets PHPStorm know...
                if ($interviewerUser instanceof User) {
                    $metricsHelper->log(new ChangeDiaryKeeperEmailEvent($diaryKeeper));
                }

                return new RedirectResponse($successUrl);
            }
        }

        return $this->render('interviewer/dashboard/household_management/reset_email.html.twig', [
            'form' => $form,
            'user' => $diaryKeeperUser,
            'action' => 'change-email',

            'areaPeriod' => $household->getAreaPeriod(),
            'diaryKeeper' => $diaryKeeper,
            'household' => $household,
        ]);
    }
}