<?php

namespace App\Form\TravelDiary\JourneyWizard;

use App\Entity\Journey\Journey;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;

class LocationsDataMapper implements DataMapperInterface
{
    /**
     * @param Journey | null $viewData
     */
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $this->mapLocationDataToForms('start', $viewData, $forms);
        $this->mapLocationDataToForms('end', $viewData, $forms);
    }
    
    protected function mapLocationDataToForms(string $locationType, ?Journey $viewData, array $forms): void
    {
        $ucfLocationType = ucfirst($locationType);
        if (isset($forms["{$locationType}Location"]) && isset($forms["{$locationType}_choice"])) {
            $choiceValues = array_map('strtolower', $forms["{$locationType}_choice"]->getConfig()->getOption('choices', []));
            switch(true) {
                case $viewData->{"getIs{$ucfLocationType}Home"}() :
                    $forms["{$locationType}Location"]->setData(null);
                    $forms["{$locationType}_choice"]->setData(LocationsType::CHOICE_HOME);
                    break;

                case in_array(strtolower((string) $viewData->{"get{$ucfLocationType}Location"}()), $choiceValues) :
                    $forms["{$locationType}Location"]->setData(null);
                    $forms["{$locationType}_choice"]->setData($viewData->{"get{$ucfLocationType}Location"}());
                    break;

                case !empty(strtolower((string) $viewData->{"get{$ucfLocationType}Location"}())) :
                    $forms["{$locationType}Location"]->setData($viewData->{"get{$ucfLocationType}Location"}());
                    $forms["{$locationType}_choice"]->setData(LocationsType::CHOICE_OTHER);
                    break;
            }
        }
    }

    /**
     * @param Journey | null $viewData
     */
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $this->mapLocationFormsToData('start', $forms, $viewData);
        $this->mapLocationFormsToData('end', $forms, $viewData);
    }

    /**
     * @param $locationType
     * @param FormInterface[] | array $forms
     */
    protected function mapLocationFormsToData($locationType, array $forms, Journey $viewData): void
    {
        $ucfLocationType = ucfirst((string) $locationType);
        if (isset($forms["{$locationType}Location"]) && isset($forms["{$locationType}_choice"])) {
            switch ($forms["{$locationType}_choice"]->getData()) {
                case LocationsType::CHOICE_HOME :
                    $viewData->{"setIs{$ucfLocationType}Home"}(true);
                    $viewData->{"set{$ucfLocationType}Location"}(null);
                    break;

                case LocationsType::CHOICE_OTHER :
                    $otherData = $forms["{$locationType}Location"]->getData();

                    if (strtolower((string) $otherData) === 'home') {
                        // User literally typed "home" (or variation thereof) into the "other" field
                        $viewData->{"setIs{$ucfLocationType}Home"}(true);
                        $viewData->{"set{$ucfLocationType}Location"}(null);
                    } else {
                        $viewData->{"setIs{$ucfLocationType}Home"}(false);
                        $viewData->{"set{$ucfLocationType}Location"}($otherData);
                    }
                    break;

                default:
                    $viewData->{"setIs{$ucfLocationType}Home"}(false);
                    $viewData->{"set{$ucfLocationType}Location"}($forms["{$locationType}_choice"]->getData());
            }
        }
    }
}