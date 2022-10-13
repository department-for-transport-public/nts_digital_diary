<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\Journey\Journey;
use Symfony\Component\Form\FormInterface;

class PurposesDataMapper extends AbstractIdPrefixedShareDataMapper
{
    /**
     * @param Journey|object $entity
     */
    public function mapEntityToForm(object $entity, FormInterface $form, string $prefix): void
    {
        $form->setData($entity->getPurpose());
    }

    /**
     * @param Journey|object $entity
     */
    public function mapFormToEntity(FormInterface $form, object $entity, string $prefix): void
    {
        $entity->setPurpose($form->getData());
    }

    public function getPrefixes(): array
    {
        return ['purpose'];
    }
}