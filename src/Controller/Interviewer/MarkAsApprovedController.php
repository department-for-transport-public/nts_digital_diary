<?php

namespace App\Controller\Interviewer;

use App\Entity\DiaryKeeper;
use App\Form\ConfirmActionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class MarkAsApprovedController extends AbstractController
{
    protected RequestStack $requestStack;
    protected WorkflowInterface $travelDiaryStateStateMachine;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack, WorkflowInterface $travelDiaryStateStateMachine)
    {
        parent::__construct($entityManager);
        $this->requestStack = $requestStack;
        $this->travelDiaryStateStateMachine = $travelDiaryStateStateMachine;
    }

    /**
     * @Route("/diary-keeper/{diaryKeeper}/mark-as-approved", name="mark_as_approved")
     */
    public function maskAsComplete(DiaryKeeper $diaryKeeper, UserInterface $user): Response
    {
        return $this->confirmAndTransition(
            $user,
            $diaryKeeper,
            'mark-as-approved',
            'interviewer/dashboard/mark_as_approved.html.twig',
            DiaryKeeper::TRANSITION_APPROVE,
        );
    }

    /**
     * @Route("/diary-keeper/{diaryKeeper}/undo-approval", name="undo_approval")
     */
    public function undoApproval(DiaryKeeper $diaryKeeper, UserInterface $user): Response
    {
        return $this->confirmAndTransition(
            $user,
            $diaryKeeper,
            'undo-approval',
            'interviewer/dashboard/undo_approval.html.twig',
            DiaryKeeper::TRANSITION_UNDO_APPROVAL
        );
    }

    protected function confirmAndTransition(UserInterface $user, DiaryKeeper $diaryKeeper, string $action, string $template, string $transition): Response
    {
        $interviewer = $this->getInterviewer($user);
        $this->checkInterviewerIsSubscribedToAreaPeriod($diaryKeeper->getHousehold()->getAreaPeriod(), $interviewer);

        if (!$this->travelDiaryStateStateMachine->can($diaryKeeper, $transition)) {
            throw new AccessDeniedHttpException();
        }

        $targetUrl = $this->generateUrl('interviewer_dashboard_household', ['household' => $diaryKeeper->getHousehold()->getId()]);
        $form = $this->createForm(ConfirmActionType::class, null, [
            'cancel_link_options' => [
                'href' => $targetUrl,
            ],
            'confirm_button_options' => [
                'label' => "$action.confirm",
                'translation_domain' => 'interviewer',
            ],
        ]);
        $form->handleRequest($this->requestStack->getCurrentRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->travelDiaryStateStateMachine->apply($diaryKeeper, $transition);
            $this->entityManager->flush();

            return new RedirectResponse($targetUrl);
        }

        return $this->render($template, [
            'diaryKeeper' => $diaryKeeper,
            'form' => $form->createView(),
            'action' => $action,
        ]);
    }
}