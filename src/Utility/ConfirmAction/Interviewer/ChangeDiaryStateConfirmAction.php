<?php

namespace App\Utility\ConfirmAction\Interviewer;

use App\Entity\DiaryKeeper;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ChangeDiaryStateConfirmAction extends AbstractConfirmAction
{
    /** @var DiaryKeeper | object */
    protected $subject;

    protected string $translationKeyPrefix;
    protected string $stateTransition;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, protected EntityManagerInterface $entityManager, protected WorkflowInterface $travelDiaryStateStateMachine, protected RouterInterface $router)
    {
        parent::__construct($formFactory, $requestStack);
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button'],
                'label' => "{$this->translationKeyPrefix}.confirm",
                'label_translation_parameters' => $this->getTranslationParameters(),
            ],
        ]);
    }

    public function getTranslationDomain(): ?string
    {
        return 'interviewer';
    }

    public function getTranslationKeyPrefix(): string
    {
        return $this->translationKeyPrefix;
    }

    public function getTranslationParameters(): array
    {
        return [
            'name' => $this->subject->getName(),
        ];
    }

    public function getExtraViewData(): array
    {
        return [
            'diaryKeeper' => $this->subject,
            'action' => $this->getTranslationKeyPrefix(),
        ];
    }

    public function doConfirmedAction($formData)
    {
        $this->travelDiaryStateStateMachine->apply($this->subject, $this->stateTransition);
        $this->entityManager->flush();
    }

    public function customController(Request $request, DiaryKeeper $diaryKeeper = null, string $translationKeyPrefix = null, string $stateTransition = null): RedirectResponse | array
    {
        $this->setSubject($diaryKeeper);
        $this->translationKeyPrefix = $translationKeyPrefix;
        $this->stateTransition = $stateTransition;
        return $this->controller(
            $request,
            $this->router->generate('interviewer_dashboard_household', ['household' => $diaryKeeper->getHousehold()->getId()])
        );
    }
}
