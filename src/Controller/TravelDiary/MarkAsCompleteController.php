<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Entity\DiaryKeeper;
use App\Form\ConfirmActionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class MarkAsCompleteController extends AbstractController
{
    /**
     * @Route("/mark-as-complete", name="mark_as_complete")
     * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
     */
    public function maskAsComplete(EntityManagerInterface $entityManager, Request $request, UserInterface $user, WorkflowInterface $travelDiaryStateStateMachine): Response
    {
        $diaryKeeper = $this->getDiaryKeeper($user);

        if (!$travelDiaryStateStateMachine->can($diaryKeeper, DiaryKeeper::TRANSITION_COMPLETE)) {
            throw new AccessDeniedHttpException();
        }

        $targetUrl = $this->generateUrl('traveldiary_dashboard');
        $form = $this->createForm(ConfirmActionType::class, null, [
            'cancel_link_options' => [
                'href' => $targetUrl,
            ],
            'confirm_button_options' => [
                'label' => "mark-as-complete.form.mark-as-complete",
                'translation_domain' => 'travel-diary',
            ],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $travelDiaryStateStateMachine->apply($diaryKeeper, DiaryKeeper::TRANSITION_COMPLETE);
            $entityManager->flush();

            return new RedirectResponse($targetUrl);
        }

        return $this->render('travel_diary/mark_as_complete/mark_as_complete.html.twig', [
            'diaryKeeper' => $diaryKeeper,
            'form' => $form->createView(),
            'action' => 'mark-as-complete',
        ]);
    }
}