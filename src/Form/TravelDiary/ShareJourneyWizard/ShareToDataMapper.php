<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use Doctrine\Common\Collections\ArrayCollection;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;

class ShareToDataMapper implements DataMapperInterface
{
    public function __construct(protected readonly PropertyMerger $propertyMerger)
    {}

    /**
     * @param Journey $viewData
     */
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var $forms FormInterface[] */
        if ($shareForm = ($forms['shareTo'] ?? false)) {
            /** @var ChoiceListInterface $choiceList */
            $choiceList = $shareForm->getConfig()->getAttribute('choice_list');
            $choices = $choiceList->getChoices();
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
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var $forms FormInterface[] */

        if ($shareForm = ($forms['shareTo'] ?? false)) {
            /** @var ChoiceListInterface $choiceList */
            $choiceList = $shareForm->getConfig()->getAttribute('choice_list');
            $choices = $choiceList->getChoices();

            // add new ones
            foreach ($shareForm->getData() as $dk) {
                $viewData->addSharedTo($this->shareJourneyWith($dk, $viewData));
                unset($choices[$dk->getId()]);
            }

            // remove any not selected
            foreach ($choices as $dk) {
                $viewData->removeSharedWithDiaryKeeper($dk);
            }
        }
    }

    protected function shareJourneyWith(DiaryKeeper $diaryKeeper, Journey $sourceJourney)
    {
        $targetJourney = $this->propertyMerger->clone($sourceJourney, Journey::SHARE_JOURNEY_CLONE_PROPERTIES);

        if ($sourceJourney->isGoingHome()) {
            $targetJourney->setPurpose(Journey::TO_GO_HOME);
        }

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