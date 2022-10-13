<?php


namespace App\FormWizard\TravelDiary;


use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\AbstractFormWizardState;
use App\FormWizard\Place;

abstract class AbstractDuplicateJourneyState extends AbstractFormWizardState
{
    protected ?Journey $subject = null;

    public function getSubject(): ?Journey
    {
        return $this->subject;
    }

    /**
     * @param null|Journey $subject
     */
    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getStageCount(): int
    {
        return count($this->subject->getStages());
    }

    public function getStageNumber(): int
    {
        return $this->getPlaceParameter('stageNumber', 0);
    }

    public function getNextStageNumber(): int
    {
        return $this->getStageNumber() + 1;
    }

    public function getContextStage(): ?Stage
    {
        return $this->getStageNumber() > 0
            ? $this->subject->getStageByNumber($this->getStageNumber())
            : null;
    }

    public function getPlaceRouteParameters($place = null): array
    {
        $place = $place ?? $this->place;
        return [
            'place' => $place,
            'stageNumber' => $place instanceof Place ? $place->getContextValue('stageNumber') : null,
        ];
    }

    public function getPreviousHistoryPlaceRouteParameters(): array
    {
        return $this->getPlaceRouteParameters($this->getPreviousHistoryPlace());
    }

}