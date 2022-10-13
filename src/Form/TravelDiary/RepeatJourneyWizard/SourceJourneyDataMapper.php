<?php

namespace App\Form\TravelDiary\RepeatJourneyWizard;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\PropertyMerger;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\Utility\TravelDiary\RepeatJourneyHelper;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

class SourceJourneyDataMapper extends DataMapper
{
    private RepeatJourneyHelper $repeatJourneyHelper;

    public function __construct(RepeatJourneyHelper $repeatJourneyHelper)
    {
        parent::__construct();
        $this->repeatJourneyHelper = $repeatJourneyHelper;
    }

    /**
     * @param RepeatJourneyState $data
     */
    public function mapFormsToData($forms, &$data): void
    {
        $forms = iterator_to_array($forms);
        $journeyIdForm = $forms['sourceJourneyId'];

        if ($journeyIdForm->getData() && $data->sourceJourneyId !== $journeyIdForm->getData()) {
            /** @var Journey $sourceJourney */
            $sourceJourney = $forms['sourceJourneyId']->getNormData();
            $journey = $this->repeatJourneyHelper->cloneSourceJourney($sourceJourney);
            $data->setSubject($journey);
        }

        parent::mapFormsToData($forms, $data);
    }
}