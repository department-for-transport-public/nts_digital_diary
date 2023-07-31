<?php

namespace App\Utility;

use App\Entity\Journey\Journey;

class SplitJourneyHelper
{
    public function getMidTime(Journey $journey): \DateTime
    {
        $startTime = $journey->getStartTime();
        $endTime = $journey->getEndTime();

        return TimeHelper::addSecondsToTime(
            $startTime,
            intval(floor(TimeHelper::differenceInSeconds($startTime, $endTime) / 2))
        );
    }

    public function whenSplitWillCrossDayBoundary(Journey $journey, \DateTime $midTime): bool
    {
        return $journey->getStartTime() > $midTime;
    }

    public function whenSplitWillCrossDayBoundaryIntoDayEight(Journey $journey, \DateTime $midTime): bool
    {
        return $this->whenSplitWillCrossDayBoundary($journey, $midTime) &&
            $journey->getDiaryDay()->getNumber() === 7;
    }

    public function isJourneyTooQuickToSplit(Journey $journey): bool
    {
        return TimeHelper::differenceInSeconds($journey->getStartTime(), $journey->getEndTime()) <= 60;
    }
}