<?php

namespace App\Controller\Interviewer\HouseholdManagement;

use App\Controller\Interviewer\AbstractController;
use App\Entity\DiaryKeeper;
use App\Utility\ConfirmAction\Interviewer\ApprovalDiaryStateConfirmAction;
use App\Utility\ConfirmAction\Interviewer\ChangeDiaryStateConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route("/diary-keeper/{diaryKeeper}", name: "diary_state_")]
class DiaryKeeperStateController extends AbstractController
{
    public function __construct(
        EntityManagerInterface      $entityManager,
        Security                    $security,
        protected RequestStack      $requestStack,
        protected WorkflowInterface $travelDiaryStateStateMachine
    ) {
        parent::__construct($entityManager, $security);
    }


    #[Route("/re-open", name: "reopen")]
    #[Template("interviewer/dashboard/household_management/diary_state/re-open.html.twig")]
    public function markAsInProgress(Request $request, ChangeDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse|array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.re-open',
            DiaryKeeper::TRANSITION_UNDO_COMPLETE
        );
    }


    #[Route("/approve", name: "approve")]
    #[Template("interviewer/dashboard/household_management/diary_state/approve.html.twig")]
    public function maskAsApproved(Request $request, ApprovalDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse|array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.approve',
            DiaryKeeper::TRANSITION_APPROVE
        );
    }

    #[Route("/un-approve", name: "un_approve")]
    #[Template("interviewer/dashboard/household_management/diary_state/un_approve.html.twig")]
    public function undoApproval(Request $request, ChangeDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse|array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.un-approve',
            DiaryKeeper::TRANSITION_UNDO_APPROVAL
        );
    }

    #[Route("/discard", name: "discard")]
    #[Template("interviewer/dashboard/household_management/diary_state/discard.html.twig")]
    public function maskAsDiscarded(Request $request, ChangeDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse|array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.discard',
            DiaryKeeper::TRANSITION_DISCARD,
        );
    }

    #[Route("/un-discard", name: "un_discard")]
    #[Template("interviewer/dashboard/household_management/diary_state/un_discard.html.twig")]
    public function undoDiscard(Request $request, ChangeDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse|array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.un-discard',
            DiaryKeeper::TRANSITION_UNDO_DISCARD,
        );
    }
}