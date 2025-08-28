<?php

namespace App\FormWizard\TravelDiary;

use App\Entity\Journey\Journey;
use Ghost\GovUkCoreBundle\FormWizard\MultipleEntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SplitJourneySubject implements MultipleEntityInterface
{
    #[Assert\Valid(groups: ['wizard.split-journey.purpose'])]
    protected Journey $destinationJourney;

    public function __construct(
        protected ?string $originalJourneyPurpose,
        #[Assert\Valid(groups: ['wizard.split-journey.midpoint-other'])]
        protected Journey $sourceJourney,
        ?Journey $destinationJourney = null,
    ) {
        $this->destinationJourney = $destinationJourney ?? $this->sourceJourney;
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
            // Only single stage journeys can be split, so only one new one gets created.
            $this->destinationJourney->getStages()->first(),
        ];
    }
}