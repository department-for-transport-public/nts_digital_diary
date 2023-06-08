<?php

namespace App\Controller\Interviewer;

use App\Entity\DiaryKeeper;
use App\Form\ConfirmActionType;
use App\Utility\ConfirmAction\Interviewer\ApprovalDiaryStateConfirmAction;
use App\Utility\ConfirmAction\Interviewer\ChangeDiaryStateConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route("/diary-keeper/{diaryKeeper}", name: "diary_state_")]
class DiaryKeeperStateController extends AbstractController
{
    protected RequestStack $requestStack;
    protected WorkflowInterface $travelDiaryStateStateMachine;

    public function __construct(EntityManagerInterface $entityManager, Security $security, RequestStack $requestStack, WorkflowInterface $travelDiaryStateStateMachine)
    {
        parent::__construct($entityManager, $security);
        $this->requestStack = $requestStack;
        $this->travelDiaryStateStateMachine = $travelDiaryStateStateMachine;
    }


    #[Route("/re-open", name: "reopen")]
    #[Template("interviewer/dashboard/diary_state/re-open.html.twig")]
    public function markAsInProgress(Request $request, ChangeDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse | array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.re-open',
            DiaryKeeper::TRANSITION_UNDO_COMPLETE
        );
    }


    #[Route("/approve", name: "approve")]
    #[Template("interviewer/dashboard/diary_state/approve.html.twig")]
    public function maskAsApproved(Request $request, ApprovalDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse | array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.approve',
            DiaryKeeper::TRANSITION_APPROVE
        );
    }

    #[Route("/un-approve", name: "un_approve")]
    #[Template("interviewer/dashboard/diary_state/un_approve.html.twig")]
    public function undoApproval(Request $request, ChangeDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse | array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.un-approve',
            DiaryKeeper::TRANSITION_UNDO_APPROVAL
        );
    }

    #[Route("/discard", name: "discard")]
    #[Template("interviewer/dashboard/diary_state/discard.html.twig")]
    public function maskAsDiscarded(Request $request, ChangeDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse | array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.discard',
            DiaryKeeper::TRANSITION_DISCARD,
        );
    }

    #[Route("/un-discard", name: "un_discard")]
    #[Template("interviewer/dashboard/diary_state/un_discard.html.twig")]
    public function undoDiscard(Request $request, ChangeDiaryStateConfirmAction $confirmAction, DiaryKeeper $diaryKeeper): RedirectResponse | array
    {
        return $confirmAction->customController(
            $request,
            $diaryKeeper,
            'diary-state.un-discard',
            DiaryKeeper::TRANSITION_UNDO_DISCARD,
        );
    }
}