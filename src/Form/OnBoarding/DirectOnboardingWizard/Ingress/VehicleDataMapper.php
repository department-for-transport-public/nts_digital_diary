<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard\Ingress;

use App\Entity\Journey\Method;
use App\Entity\Vehicle;
use App\Repository\Journey\MethodRepository;
use Symfony\Component\Form\DataMapperInterface;

class VehicleDataMapper implements DataMapperInterface
{
    public function __construct(protected MethodRepository $methodRepository) {}

    public function mapDataToForms($viewData, \Traversable $forms): void
    {
    }

    /**
     * @param Vehicle $viewData
     */
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);

        $methodCode = $forms['method']->getData();
        $methodCode = is_numeric($methodCode) ? intval($methodCode) : 'invalid';

        $viewData
            ->setFriendlyName($forms['name']->getData())
            ->setMethod($this->methodRepository->findOneBy([
                'code' => $methodCode === 7 ? null : $methodCode,
                'type' => Method::TYPE_PRIVATE
            ]))
            // Driver is mapped in the parent household data mapper, as it needs resolving to a DK
        ;
    }
}
