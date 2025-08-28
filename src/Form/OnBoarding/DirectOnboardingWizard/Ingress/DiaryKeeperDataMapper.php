<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard\Ingress;

use App\Entity\DiaryKeeper;
use Symfony\Component\Form\DataMapperInterface;

class DiaryKeeperDataMapper implements DataMapperInterface
{
   public function mapDataToForms($viewData, \Traversable $forms): void
    {
    }

    /**
     * @param DiaryKeeper $viewData
     */
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);

        $viewData
            ->setName($forms['name']->getData())
            ->setIsAdult(match($forms['adult']->getData()){
                "1" => true,
                "0" => false,
                default => null
            })
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
        ;
    }
}