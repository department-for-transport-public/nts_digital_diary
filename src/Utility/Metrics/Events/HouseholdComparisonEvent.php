<?php

namespace App\Utility\Metrics\Events;

class HouseholdComparisonEvent extends AbstractEvent
{
    public function __construct(string $householdSerial, int $day)
    {
        $this->diarySerial = $householdSerial;
        $this->metadata['day'] = $day;
    }

    public function getName(): string
    {
        return 'Interviewer: household compare';
    }
}