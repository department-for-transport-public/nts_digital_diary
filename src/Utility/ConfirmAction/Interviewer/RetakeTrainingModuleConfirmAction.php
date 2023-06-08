<?php

namespace App\Utility\ConfirmAction\Interviewer;

use App\Entity\InterviewerTrainingRecord;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\InterviewerTraining\InterviewerTrainingHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;

class RetakeTrainingModuleConfirmAction extends AbstractConfirmAction
{
    /** @var InterviewerTrainingRecord | object */
    protected $subject;
    protected InterviewerTrainingHelper $trainingHelper;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, InterviewerTrainingHelper $trainingHelper)
    {
        parent::__construct($formFactory, $requestStack);
        $this->trainingHelper = $trainingHelper;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button'],
                'label' => 'training.retake.confirm-label',
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
        return 'training.retake';
    }

    public function getTranslationParameters(): array
    {
        return [
            'moduleName' => new TranslatableMessage("training.module.title.{$this->subject->getModuleName()}", [], 'interviewer'),
            'moduleNumber' => new TranslatableMessage('training.module.number', ['number' => $this->subject->getModuleNumber()], 'interviewer'),
        ];
    }

    public function doConfirmedAction($formData)
    {
        $this->trainingHelper->createTrainingRecordForModule($this->subject->getInterviewer(), $this->subject->getModuleName());
    }
}
