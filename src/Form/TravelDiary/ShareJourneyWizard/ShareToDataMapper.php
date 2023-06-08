<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\FormWizard\PropertyMerger;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;

class ShareToDataMapper implements DataMapperInterface
{
    private PropertyMerger $propertyMerger;

    public function __construct(PropertyMerger $propertyMerger)
    {
        $this->propertyMerger = $propertyMerger;
    }

    /**
     * @param Journey $viewData
     */
    public function mapDataToForms($viewData, \Traversable $forms)
    {
        $forms = iterator_to_array($forms);
        /** @var $forms FormInterface[] */
        if ($shareForm = ($forms['shareTo'] ?? false)) {
            /** @var ChoiceLoaderInterface $choiceLoader */
            $choiceLoader = $shareForm->getConfig()->getOption('choice_loader');
            $choices = $choiceLoader->loadChoiceList()->getChoices();
            $selected = new ArrayCollection();
            /** @var Journey $sharedTo */
            foreach ($viewData->getSharedTo() as $sharedTo) {
                $selected->add($choices[$sharedTo->getDiaryDay()->getDiaryKeeper()->getId()]);
            }
            $shareForm->setData($selected);
        }
    }

    /**
     * @param Journey $viewData
     */
    public function mapFormsToData(\Traversable $forms, &$viewData)
    {
        $forms = iterator_to_array($forms);
        /** @var $forms FormInterface[] */

        if ($shareForm = ($forms['shareTo'] ?? false)) {
            /** @var ChoiceLoaderInterface $choiceLoader */
            $choiceLoader = $shareForm->getConfig()->getOption('choice_loader');
            $removeChoices = $choiceLoader->loadChoiceList()->getChoices();

            // add new ones
            foreach ($shareForm->getData() as $dk) {
                $viewData->addSharedTo($this->shareJourneyWith($dk, $viewData));
                unset($removeChoices[$dk->getId()]);
            }

            // remove any not selected
            foreach ($removeChoices as $dk) {
                $viewData->removeSharedWithDiaryKeeper($dk);
            }

        }
    }

    protected function shareJourneyWith(DiaryKeeper $diaryKeeper, Journey $sourceJourney)
    {
        $targetJourney = $this->propertyMerger->clone($sourceJourney, Journey::SHARE_JOURNEY_CLONE_PROPERTIES);

        /** @var Stage $sourceStage */
        foreach ($sourceJourney->getStages()->toArray() as $sourceStage) {
            $targetStage = $this->propertyMerger->clone($sourceStage, Stage::SHARE_JOURNEY_CLONE_PROPERTIES);
            $targetJourney->addStage($targetStage);

            if ($sourceStage->getMethod()->getType() === Method::TYPE_PRIVATE && $sourceStage->getIsDriver()) {
                $targetStage->setIsDriver(false);
            }
        }

        $targetJourney->setDiaryDay($diaryKeeper->getDiaryDayByNumber($sourceJourney->getDiaryDay()->getNumber()));

        return $targetJourney;
    }
}