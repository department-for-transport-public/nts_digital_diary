<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard\Ingress;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Vehicle;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;

class HouseholdDataMapper implements DataMapperInterface
{
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
    }

    /**
     * @param Household $viewData
     */
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var array<FormInterface> $forms */

        $viewData
            ->setAddressNumber($forms['add']->getData())
            ->setHouseholdNumber($forms['h']->getData())
            ->setDiaryWeekStartDate($forms['start']->getData())
            ->setCheckLetter($forms['cl']->getData())
        ;

        $diaryKeepers = $viewData->getDiaryKeepers();
        foreach ($forms['dk']->all() as $idx => $item) {
            /** @var DiaryKeeper $dk */
            $dk = $item->getData();
            $diaryKeepers[] = $dk
                ->setNumber(intval($idx))
                ->setHousehold($viewData);
        }

        $vehicles = $viewData->getVehicles();
        foreach ($forms['v']->all() as $idx => $item) {
            /** @var Vehicle $v */
            $v = $item->getData();
            $driverCapiNumber = $item->get('driver')->getData() ?? -1;
            $vehicles[] = $v
                ->setCapiNumber(intval($idx))
                ->setPrimaryDriver($viewData->getDiaryKeeperByNumber(intval($driverCapiNumber)))
                ->setHousehold($viewData)
            ;
        }
    }
}