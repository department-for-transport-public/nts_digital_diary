<?php

namespace App\FormWizard\TravelDiary;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Journey as JourneyEntity;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use Doctrine\Common\Collections\Collection;

class ShareJourneyState extends AbstractDuplicateJourneyState
{
    const STATE_INTRO = 'introduction';
    const STATE_WHO_WITH = 'who';
    const STATE_PURPOSES = 'purposes';
    const STATE_STAGE_DETAILS = 'stage-details';
    const STATE_FINISH = 'finish';

    const TRANSITION_INTRO_TO_WHO_WITH = 'intro-to-who';
    const TRANSITION_INTRO_TO_FINISH = 'intro-to-finish'; // For when there are no available recipients
    const TRANSITION_WHO_WITH_TO_PURPOSE = 'who-to-purpose';
    const TRANSITION_PURPOSE_TO_STAGE_DETAILS = 'purpose-to-stage-details';
    const TRANSITION_PURPOSE_TO_FINISH = 'purpose-to-finish';
    const TRANSITION_STAGE_TO_NEXT_STAGE = 'stage-to-next-stage';
    const TRANSITION_STAGE_DETAILS_TO_FINISH = 'stage-details-to-finish';


    protected ?JourneyEntity $subject = null;

    public function getSubject(): ?JourneyEntity
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getNextStageNumber(): int
    {
        return $this->findNextPrivateOrPublicStage($this->getStageNumber())->getNumber();
    }

    public function hasNextStage(): bool
    {
        return !is_null($this->findNextPrivateOrPublicStage($this->getStageNumber()));
    }

    protected function findNextPrivateOrPublicStage(int $startFrom): ?Stage
    {
        foreach (array_slice($this->getSubject()->getStages()->toArray(), $startFrom) as $stage) {
            /** @var $stage Stage */
            if ($stage->getMethod()->getType() !== Method::TYPE_OTHER) {
                return $stage;
            }
        }
        return null;
    }

    public function getContextSharedToStagesBeingAdded(): Collection
    {
        return $this->subject->getSharedToJourneysBeingAdded()->map(
            fn(Journey $j) => $j->getStageByNumber($this->getStageNumber())
        );
    }
}