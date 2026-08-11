<?php


namespace App\FormWizard\TravelDiary;


use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractDuplicateJourneyState extends AbstractFormWizardState
{
    #[Assert\Valid]
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
        $place ??= $this->place;
        return [
            'place' => $place,
            'stageNumber' => $place instanceof Place ? $place->getContextValue('stageNumber') : null,
        ];
    }
}