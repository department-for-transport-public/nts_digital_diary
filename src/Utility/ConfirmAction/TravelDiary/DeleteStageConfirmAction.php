<?php

namespace App\Utility\ConfirmAction\TravelDiary;

use App\Entity\Journey\Stage;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteStageConfirmAction extends AbstractConfirmAction
{
    /** @var Stage | object */
    protected $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, protected EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
    }

    public function getExtraViewData(): array
    {
        $journey = $this->subject->getJourney();
        $day = $journey->getDiaryDay();

        return [
            'journey' => $journey,
            'day' => $day,
            'diaryKeeper' => $day->getDiaryKeeper(),
            'stage' => $this->subject,
            'action' => 'delete-stage',
        ];
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
                'label' => $this->getTranslationKeyPrefix().".confirm-label",
            ],
        ]);
    }

    public function getTranslationDomain(): ?string
    {
        return 'travel-diary';
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'stage.delete';
    }

    public function getTranslationParameters(): array
    {
        return [
            'stageNumber' => $this->getSubject()->getNumber(),
        ];
    }

    public function doConfirmedAction($formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}