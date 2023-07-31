<?php

namespace App\FormWizard\TravelDiary;

use App\FormWizard\MultipleEntityInterface;
use App\Entity\Journey\Journey;
use Symfony\Component\Validator\Constraints as Assert;

class SplitJourneySubject implements MultipleEntityInterface
{
    protected ?string $originalJourneyPurpose;

    /** @Assert\Valid(groups={"wizard.split-journey.midpoint-other"}) */
    protected Journey $sourceJourney;

    /** @Assert\Valid(groups={"wizard.split-journey.purpose"}) */
    protected Journey $destinationJourney;

    public function __construct(
        ?string $originalJourneyPurpose,
        Journey $sourceJourney,
        ?Journey $destinationJourney = null,
    ) {
        $this->originalJourneyPurpose = $originalJourneyPurpose;
        $this->sourceJourney = $sourceJourney;
        $this->destinationJourney = $destinationJourney ?? $sourceJourney;
    }

    public function getOriginalJourneyPurpose(): ?string
    {
        return $this->originalJourneyPurpose;
    }

    public function getSourceJourney(): Journey
    {
        return $this->sourceJourney;
    }

    public function getDestinationJourney(): ?Journey
    {
        return $this->destinationJourney;
    }

    public function isDestinationToHome(): bool
    {
        return $this->destinationJourney->getIsEndHome();
    }

    public function getEntitiesToPersist(): array
    {
        return [
            $this->destinationJourney,
            $this->destinationJourney->getStages()->first(),
        ];
    }
}