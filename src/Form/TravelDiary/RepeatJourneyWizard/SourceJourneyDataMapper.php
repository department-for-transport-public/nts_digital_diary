<?php

namespace App\Form\TravelDiary\RepeatJourneyWizard;

use App\Entity\Journey\Journey;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\Utility\TravelDiary\RepeatJourneyHelper;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

class SourceJourneyDataMapper extends DataMapper
{
    public function __construct(protected readonly RepeatJourneyHelper $repeatJourneyHelper)
    {
        parent::__construct();
    }

    /**
     * @param RepeatJourneyState $data
     */
    public function mapFormsToData(\Traversable $forms, mixed &$data): void
    {
        $traversableForms = $forms;

        $forms = iterator_to_array($forms);
        $journeyIdForm = $forms['sourceJourneyId'];

        if ($journeyIdForm->getData() && $data->sourceJourneyId !== $journeyIdForm->getData()) {
            /** @var Journey $sourceJourney */
            $sourceJourney = $forms['sourceJourneyId']->getNormData();
            $journey = $this->repeatJourneyHelper->cloneSourceJourney($sourceJourney);
            $data->setSubject($journey);
        }

        parent::mapFormsToData($traversableForms, $data);
    }
}
