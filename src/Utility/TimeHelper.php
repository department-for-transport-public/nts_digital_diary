<?php

namespace App\Utility;

class TimeHelper
{
    public const ONE_MINUTE = 60;
    public const ONE_HOUR = self::ONE_MINUTE * 60;
    public const ONE_DAY = self::ONE_HOUR * 24;

    public static function differenceInSeconds(\DateTime $timeOne, \DateTime $timeTwo): int
    {
        $secondsOne = TimeHelper::timeToSeconds($timeOne);
        $secondsTwo = TimeHelper::timeToSeconds($timeTwo);

        if ($secondsOne > $secondsTwo) {
            $secondsTwo += self::ONE_DAY;
        }

        return $secondsTwo - $secondsOne;
    }

    public static function timeToSeconds(\DateTime $time): int
    {
        $hours = intval($time->format('H'));
        $mins = intval($time->format('i'));
        $seconds = intval($time->format('s'));

        return ($hours * self::ONE_HOUR) + ($mins * self::ONE_MINUTE) + $seconds;
    }

    public static function secondsToTime(int $seconds): \DateTime
    {
        $hours = intval(floor($seconds % self::ONE_HOUR));
        $seconds -= ($hours * self::ONE_HOUR);
        $mins = intval(floor($seconds % self::ONE_MINUTE));
        $seconds -= ($mins * self::ONE_MINUTE);

        return (new \DateTime('1970-01-01 00:00:00'))
            ->setTime($hours, $mins, $seconds);
    }

    public static function addSecondsToTime(\DateTime $time, int $secondsToAdd): \DateTime
    {
        $seconds = self::timeToSeconds($time) + $secondsToAdd;

        if ($seconds >= self::ONE_DAY) {
            $seconds -= self::ONE_DAY;
        }

        return self::secondsToTime($seconds);
    }
}