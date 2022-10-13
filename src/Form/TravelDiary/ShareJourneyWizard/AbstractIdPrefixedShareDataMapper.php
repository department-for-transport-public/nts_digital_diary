<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Form\IdPrefixHelper;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;

abstract class AbstractIdPrefixedShareDataMapper implements DataMapperInterface
{
    abstract public function mapEntityToForm(object $entity, FormInterface $form, string $prefix): void;
    abstract public function mapFormToEntity(FormInterface $form, object $entity, string $prefix): void;
    abstract public function getPrefixes(): array;

    public function mapDataToForms($viewData, \Traversable $forms)
    {
        $this->map($forms, $viewData, function(FormInterface $form, object $entity, string $prefix) {
            $this->mapEntityToForm($entity, $form, $prefix);
        });
    }

    public function mapFormsToData(\Traversable $forms, &$viewData)
    {
        $this->map($forms, $viewData, function(FormInterface $form, object &$entity, string $prefix) {
            $this->mapFormToEntity($form, $entity, $prefix);
        });
    }

    protected function map(\Traversable $forms, &$viewData, callable $callback): void
    {
        $forms = iterator_to_array($forms);
        $idPrefixHelper = new IdPrefixHelper($this->getPrefixes());

        /** @var FormInterface[] $forms */
        if (!$idPrefixHelper->isRelevantForm($forms)) {
            return;
        }

        if ($viewData instanceof Stage) {
            $journey = $viewData->getJourney();
            $stageIndex = $journey->getStages()->indexOf($viewData);
        } else if ($viewData instanceof Journey) {
            $journey = $viewData;
        } else {
            return;
        }

        $journeyData = $journey->getSharedToJourneysBeingAdded();

        foreach($forms as $formName => $form) {
            if ($prefix = $idPrefixHelper->getRelevantPrefix($formName)) {
                if ($diaryKeeperId = $idPrefixHelper->getIdFromFormName($formName, $prefix)) {
                    if (!isset($journeyData[$diaryKeeperId])) {
                        continue;
                    }

                    $relevantJourney = $journeyData[$diaryKeeperId];
                    if ($viewData instanceof Stage) {
                        /** @noinspection PhpUndefinedVariableInspection */
                        $entity = $relevantJourney->getStages()->get($stageIndex);
                    } else {
                        $entity = $relevantJourney;
                    }

                    $callback($form, $entity, $prefix);
                }
            }
        }
    }
}