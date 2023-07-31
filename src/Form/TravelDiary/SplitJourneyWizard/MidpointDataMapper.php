<?php

namespace App\Form\TravelDiary\SplitJourneyWizard;

use App\Entity\Journey\Journey;
use App\Form\TravelDiary\AbstractLocationType;
use App\FormWizard\TravelDiary\SplitJourneySubject;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Traversable;

class MidpointDataMapper implements DataMapperInterface
{
    /**
     * @param Journey | null $viewData
     * @param Traversable|iterable $forms
     */
    public function mapDataToForms($viewData, iterable $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if ($viewData === null) {
            return;
        }

        if (!$viewData instanceof SplitJourneySubject) {
            throw new UnexpectedTypeException($viewData, SplitJourneySubject::class);
        }

        if (!isset($forms["midpointLocation"]) || !isset($forms["midpoint_choice"])) {
            return;
        }

        $choiceValues = array_flip(array_map(
            'strtolower',
            $forms["midpoint_choice"]->getConfig()->getOption('choices', [])
        ));

        $sourceJourney = $viewData->getSourceJourney();
        $dataIsHome = $sourceJourney->getIsEndHome();
        $dataLocation = $sourceJourney->getEndLocation();
        $lcDataLocation = strtolower($dataLocation);

        $formChoice = null;
        $formLocation = null;

        if ($dataIsHome) {
            $formChoice = AbstractLocationType::CHOICE_HOME;
        } else if (array_key_exists($lcDataLocation, $choiceValues)) {
            $formChoice = $choiceValues[$lcDataLocation];
        } else if ($dataIsHome !== null && $dataLocation !== null){
            $formChoice = AbstractLocationType::CHOICE_OTHER;
            $formLocation = $dataLocation;
        }

        $forms["midpointLocation"]->setData($formLocation);
        $forms["midpoint_choice"]->setData($formChoice);
    }

    /**
     * @param Journey | null $viewData
     * @param Traversable|iterable $forms
     */
    public function mapFormsToData(iterable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if ($viewData === null) {
            return;
        }

        if (!$viewData instanceof SplitJourneySubject) {
            throw new UnexpectedTypeException($viewData, SplitJourneySubject::class);
        }

        if (!isset($forms["midpointLocation"]) || !isset($forms["midpoint_choice"])) {
            return;
        }

        [$formChoice, $formLocation] = $this->getNormalisedFormData($forms);

        $disallowedChoices = $this->getDisallowedChoices($viewData);

        foreach($disallowedChoices as $disallowedChoice) {
            $disallowedChoice = strtolower($disallowedChoice);

            if (
                ($formChoice && strtolower($formChoice) === $disallowedChoice) ||
                ($formLocation && strtolower($formLocation) === $disallowedChoice)
            ) {
                throw new TransformationFailedException('Invalid choice', 0, null, 'wizard.split-journey.midpoint.invalid-choice');
            }
        }

        if ($formChoice === AbstractLocationType::CHOICE_HOME) {
            $this->setData($viewData, true, null);
        } else if ($formChoice === AbstractLocationType::CHOICE_OTHER) {
            $this->setData($viewData, false, $formLocation);
        } else if ($formChoice !== null) {
            $this->setData($viewData, false, $formChoice);
        } else {
            $this->setData($viewData, null, null);
        }
    }

    /**
     * Abstracted out so that this code can also be used by the validation_groups normaliser in MidpointType
     *
     * @return array{?string, ?string}
     */
    public function getNormalisedFormData(array|FormInterface $form): array
    {
        if (is_array($form)) {
            $formChoice = $form["midpoint_choice"]->getData();
            $formLocation = $form["midpointLocation"]->getData();
        } else {
            $formChoice = $form->get('midpoint_choice')->getData();
            $formLocation = $form->get('midpointLocation')->getData();
        }

        if ($formChoice !== AbstractLocationType::CHOICE_OTHER) {
            $formLocation = null;
        }

        // User literally typed "home" into the "other" field
        if (
            $formChoice === AbstractLocationType::CHOICE_OTHER &&
            strtolower($formLocation) == 'home'
        ) {
            $formChoice = AbstractLocationType::CHOICE_HOME;
            $formLocation = null;
        }

        return [$formChoice, $formLocation];
    }

    public function getDisallowedChoices(SplitJourneySubject $viewData): array
    {
        $disallowedChoices = [];

        $sourceJourney = $viewData->getSourceJourney();
        $destinationJourney = $viewData->getDestinationJourney();

        $addDisallowedChoice = function(string $choice) use (&$disallowedChoices) {
            $disallowedChoices[$choice] = $choice;
        };

        if ($sourceJourney->getIsStartHome() || $destinationJourney->getIsEndHome()) {
            $addDisallowedChoice('home');
        }

        if (!$sourceJourney->getIsStartHome()) {
            $addDisallowedChoice($sourceJourney->getStartLocation());
        }

        if (!$destinationJourney->getIsEndHome()) {
            $addDisallowedChoice($destinationJourney->getEndLocation());
        }

        return $disallowedChoices;
    }

    protected function setData(SplitJourneySubject $viewData, ?bool $isHome, ?string $location): void
    {
        $sourceJourney = $viewData->getSourceJourney();
        $destinationJourney = $viewData->getDestinationJourney();

        $sourcePurpose = $isHome ?
            Journey::TO_GO_HOME :
            $viewData->getOriginalJourneyPurpose();

        $sourceJourney
            ->setPurpose($sourcePurpose)
            ->setIsEndHome($isHome)
            ->setEndLocation($location);

        $destinationJourney
            ->setIsStartHome($isHome)
            ->setStartLocation($location);

        // If midpoint is home, then pre-fill the destination purpose
        if ($isHome && $destinationJourney->getPurpose() === null) {
            $destinationJourney->setPurpose($viewData->getOriginalJourneyPurpose());
        }
    }
}