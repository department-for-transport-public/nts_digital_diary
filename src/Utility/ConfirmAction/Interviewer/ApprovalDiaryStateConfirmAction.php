<?php

namespace App\Utility\ConfirmAction\Interviewer;

use App\Entity\DiaryDay;
use App\Form\DoubleConfirmActionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Workflow\WorkflowInterface;

class ApprovalDiaryStateConfirmAction extends ChangeDiaryStateConfirmAction
{
    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, EntityManagerInterface $entityManager, WorkflowInterface $travelDiaryStateStateMachine, RouterInterface $router, protected Security $security)
    {
        parent::__construct($formFactory, $requestStack, $entityManager, $travelDiaryStateStateMachine, $router);
    }

    protected function hasEmptyDays(): bool
    {
        return !$this->subject
            ->getDiaryDays()
            ->filter(fn(DiaryDay $d) => $d->getJourneys()->isEmpty())
            ->isEmpty();
    }

    public function getExtraViewData(): array
    {
        return array_merge(parent::getExtraViewData(), [
            'hasEmptyDays' => $this->hasEmptyDays(),
        ]);
    }

    public function getFormClass(): string
    {
        return $this->hasEmptyDays()
            ? DoubleConfirmActionType::class
            : parent::getFormClass();
    }

    public function getFormOptions(): array
    {
        return array_merge(
            parent::getFormOptions(),
            $this->hasEmptyDays()
                ?
                    [
                        'confirmation_checkbox_options' => [
                            'label' => 'diary-state.approve.confirm-empty-journeys',
                            'label_translation_parameters' => $this->getTranslationParameters(),
                        ],
                    ]
                : []
        );
    }

    public function doConfirmedAction($formData)
    {
        if (($formData['confirmation'] ?? false) === true) {
            $this->subject
                ->setEmptyDaysVerifiedBy($this->security->getUser()->getUserIdentifier())
                ->setEmptyDaysVerifiedAt(new \DateTimeImmutable());
        }
        parent::doConfirmedAction($formData);
    }
}
