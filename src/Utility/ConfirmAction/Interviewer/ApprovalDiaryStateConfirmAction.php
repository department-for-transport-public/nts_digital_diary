<?php

namespace App\Utility\ConfirmAction\Interviewer;

use App\Form\TravelDiary\ApproveDiaryConfirmActionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ApprovalDiaryStateConfirmAction extends ChangeDiaryStateConfirmAction
{
    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        WorkflowInterface $travelDiaryStateStateMachine,
        RouterInterface $router,
        protected Security $security
    ) {
        parent::__construct($formFactory, $requestStack, $entityManager, $travelDiaryStateStateMachine, $router);
    }

    public function getExtraViewData(): array
    {
        return array_merge(parent::getExtraViewData(), [
            'hasEmptyDays' => $this->subject->hasEmptyDays(),
        ]);
    }

    public function getFormClass(): string
    {
        return ApproveDiaryConfirmActionType::class;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'diary_keeper' => $this->getSubject(),
        ]);
    }

    public function doConfirmedAction($formData): void
    {
        // if the form validated, all items were checked
        $this->subject
            ->setApprovalChecklistVerifiedBy($this->security->getUser()->getUserIdentifier())
            ->setApprovalChecklistVerifiedAt(new \DateTimeImmutable());

        parent::doConfirmedAction($formData);
    }
}
