<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\Journey\Stage;
use Symfony\Component\Form\FormInterface;

class StageDetailsDataMapper extends AbstractIdPrefixedShareDataMapper
{
    /**
     * @param Stage|object $entity
     */
    public function mapEntityToForm(object $entity, FormInterface $form, string $prefix): void
    {
        switch($prefix) {
            case 'isDriver':
                $form->setData($entity->getIsDriver());
                break;
            case 'parkingCost':
                $form->setData($entity->getParkingCost());
                break;
            case 'ticketCost':
                $form->setData($entity->getTicketCost());
                break;
            case 'ticketType':
                $form->setData($entity->getTicketType());
                break;
        }
    }

    /**
     * @param Stage|object $entity
     */
    public function mapFormToEntity(FormInterface $form, object $entity, string $prefix): void
    {
        switch($prefix) {
            case 'isDriver':
                $entity->setIsDriver($form->getData());
                break;
            case 'parkingCost':
                $entity->setParkingCost($form->getData());
                break;
            case 'ticketCost':
                $entity->setTicketCost($form->getData());
                break;
            case 'ticketType':
                $entity->setTicketType($form->getData());
                break;
        }
    }

    public function getPrefixes(): array
    {
        return ['isDriver', 'parkingCost', 'ticketCost', 'ticketType'];
    }
}