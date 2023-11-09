<?php

namespace App\Utility\ConfirmAction\TravelDiary;

use App\Entity\Journey\Journey;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\DateTimeFormats;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteJourneyConfirmAction extends AbstractConfirmAction
{
    /** @var Journey | object */
    protected $subject;
    protected EntityManagerInterface $entityManager;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
        $this->entityManager = $entityManager;
    }

    public function getExtraViewData(): array
    {
        $day = $this->subject->getDiaryDay();

        return [
            'journey' => $this->subject,
            'day' => $day,
            'diaryKeeper' => $day->getDiaryKeeper(),
            'action' => 'delete-journey',
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
        return 'journey.delete';
    }

    public function getTranslationParameters(): array
    {
        return [
            'startLocation' => $this->subject->getStartLocationForDisplay(),
            'endLocation' => $this->subject->getEndLocationForDisplay(),
            'time' => $this->subject->getStartTime()->format(DateTimeFormats::TIME_SHORT),
        ];
    }

    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}