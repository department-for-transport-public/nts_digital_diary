<?php

namespace App\FormWizard\TravelDiary;

use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use App\Entity\Journey\Stage;

class StageState extends AbstractFormWizardState
{
    public const STATE_INTERMEDIARY = 'intermediary';

    public const STATE_METHOD = 'method';
    public const STATE_DETAILS = 'details';
    public const STATE_VEHICLE = 'vehicle';
    public const STATE_DRIVER_AND_PARKING = 'driver-and-parking';
    public const STATE_TICKET_TYPE = 'ticket-type';
    public const STATE_TICKET_COST_AND_BOARDINGS = 'ticket-cost-and-boardings';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_INTERMEDIATY_TO_METHOD = self::STATE_INTERMEDIARY . '-to-' . self::STATE_METHOD;

    public const TRANSITION_METHOD_TO_DETAILS = self::STATE_METHOD . '-to-' . self::STATE_DETAILS;
    public const TRANSITION_DETAILS_TO_FINISH = self::STATE_DETAILS . '-to-' . self::STATE_FINISH;

    public const TRANSITION_DETAILS_TO_VEHICLE = self::STATE_DETAILS . '-to-' . self::STATE_VEHICLE;
    public const TRANSITION_VEHICLE_TO_FINISH = self::STATE_VEHICLE . '-to-' . self::STATE_FINISH;

    public const TRANSITION_VEHICLE_TO_DRIVER_AND_PARKING = self::STATE_VEHICLE . '-to-' . self::STATE_DRIVER_AND_PARKING;
    public const TRANSITION_DRIVER_AND_PARKING_TO_FINISH = self::STATE_DRIVER_AND_PARKING . '-to-' . self::STATE_FINISH;

    public const TRANSITION_DETAILS_TO_TICKET_TYPE = self::STATE_DETAILS . '-to-' . self::STATE_TICKET_TYPE;
    public const TRANSITION_TICKET_TYPE_TO_TICKET_COST = self::STATE_TICKET_TYPE . '-to-' . self::STATE_TICKET_COST_AND_BOARDINGS;
    public const TRANSITION_TICKET_COST_TO_FINISH = self::STATE_TICKET_COST_AND_BOARDINGS . '-to-' . self::STATE_FINISH;


    private ?Stage $subject = null;

    public function getSubject(): ?Stage
    {
        return $this->subject ?? null;
    }

    /**
     * @param Stage|null $subject
     */
    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMethodType(): ?string
    {
        return $this->getSubject()->getMethod()->getType() ?? null;
    }

    public function isAdultDiary(): bool
    {
        return $this->getSubject()->getJourney()->getDiaryDay()->getDiaryKeeper()->getIsAdult() ?? false;
    }
}