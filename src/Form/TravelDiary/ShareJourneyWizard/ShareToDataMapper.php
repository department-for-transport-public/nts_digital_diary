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

        /**
         * When sharing a journey, the purpose is not cloned (see Journey::SHARE_JOURNEY_CLONE_PROPERTIES) because
         * each recipient may have a different purpose. For "going home" journeys, we pre-fill the purpose here so
         * that the purposes form shows "Go home" by default — but the user can still change it.
         *
         * Journey::setGoHomePurposeWhenGoingHome() (a PrePersist lifecycle callback) also sets the purpose for
         * going-home journeys, but only when the purpose is empty — so it won't overwrite a user-chosen purpose.
         * This mapper handles the pre-fill before the form is shown in the share journey wizard; the lifecycle
         * callback is for other relevant flows.
         */
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