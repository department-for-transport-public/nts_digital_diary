<?php

namespace App\Form\TravelDiary\CombinedReflowWizard;

use App\Entity\Journey\Journey;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\Utility\TravelDiary\DuplicateJourneyHelper;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

class SourceDayJourneyDataMapper extends DataMapper
{
    public function __construct(protected readonly DuplicateJourneyHelper $duplicateJourneyHelper, protected readonly string $journeyType)
    {
        parent::__construct();
    }

    /**
     * @param RepeatJourneyState $data
     */
    public function mapFormsToData(\Traversable $forms, mixed &$data): void
    {
        $forms = iterator_to_array($forms);
        $selectedDayNumber = $forms['sourceDayNumber']->getData();
        // needs to be a null check, as practice day number (0) is falsy
        if ($selectedDayNumber === null) {
            return;
        }
        $data->sourceDayNumber = $selectedDayNumber;

        $journeyIdForm = $forms["day-{$selectedDayNumber}-journey"];
        if ($journeyIdForm->getData() && $data->sourceJourneyId !== $journeyIdForm->getData()) {
            /** @var Journey $sourceJourney */
            $sourceJourney = $journeyIdForm->getNormData();
            $journey = $this->duplicateJourneyHelper->cloneSourceJourney($sourceJourney, $this->journeyType);
            $data
                ->setSubject($journey)
                ->sourceJourneyId = $sourceJourney->getId();
        }
    }
}
