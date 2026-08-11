<?php

namespace App\Utility\Metrics\Events;

use App\Entity\Household;
use App\EventSubscriber\Onboarding\OnboardingType;
use App\Utility\Metrics\MetricsHelper;

class OnboardingCompleteEvent extends AbstractEvent
{
    public function __construct(
        Household $household,
        OnboardingType $onboardingType,
    )
    {
        $this->diarySerial = $household->getSerialNumber(null, ...MetricsHelper::GET_SERIAL_METHOD_ARGS);
        $this->metadata['onboardingType'] = $onboardingType->value;
        $this->metadata['diaryWeekStartDate'] = $household->getDiaryWeekStartDate()->format('Y-m-d');
        $this->metadata['diaryKeepers'] = $household->getDiaryKeepers()->count();
        $this->metadata['adultDiaries'] = [];
        foreach ($household->getDiaryKeepers() as $diaryKeeper) {
            if ($diaryKeeper->getIsAdult()) {
                $this->metadata['adultDiaries'][] = $diaryKeeper->getNumber();
            }
        }
    }

    public function getName(): string
    {
        return 'Onboarding: complete';
    }
}
