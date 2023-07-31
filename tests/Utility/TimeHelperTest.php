<?php

namespace App\Tests\Utility;

use App\Utility\TimeHelper;
use PHPUnit\Framework\TestCase;

class TimeHelperTest extends TestCase
{
    public function dataTimeToSeconds(): array
    {
        return [
            [0, new \DateTime('1970-01-01 00:00:00')],
            [100, new \DateTime('1970-01-01 00:01:40')],
            [3705, new \DateTime('1970-01-01 01:01:45')],
            [86399, new \DateTime('1970-01-01 23:59:59')],
        ];
    }

    /** @dataProvider dataTimeToSeconds */
    public function testTimeToSeconds(int $expectedToSeconds, \DateTime $time): void
    {
        $this->assertEquals($expectedToSeconds, TimeHelper::timeToSeconds($time));
    }

    public function dataSecondsToTime(): array
    {
        return [
            [new \DateTime('1970-01-01 00:00:00'), 0],
            [new \DateTime('1970-01-01 00:01:40'), 100],
            [new \DateTime('1970-01-01 01:01:45'), 3705],
            [new \DateTime('1970-01-01 23:59:59'), 86399],
        ];
    }

    /** @dataProvider dataSecondsToTime */
    public function testSecondsToTime(\DateTime $expectedDateTime, int $seconds): void
    {
        $this->assertEquals($expectedDateTime, TimeHelper::secondsToTime($seconds));
    }

    public function dataDifferenceInSeconds(): array
    {
        return [
            [0, new \DateTime('1970-01-01 00:00:00'), new \DateTime('1970-01-01 00:00:00')],
            [10, new \DateTime('1970-01-01 00:00:00'), new \DateTime('1970-01-01 00:00:10')],
            [125, new \DateTime('1970-01-01 00:01:40'), new \DateTime('1970-01-01 00:03:45')],
            [18085, new \DateTime('1970-01-01 01:01:45'), new \DateTime('1970-01-01 06:03:10')],

            // time2 > time1 (i.e. day wrap)
            [5415, new \DateTime('1970-01-01 23:45:00'), new \DateTime('1970-01-01 01:15:15')],
            [86399, new \DateTime('1970-01-01 00:00:01'), new \DateTime('1970-01-01 00:00:00')],
        ];
    }

    /** @dataProvider dataDifferenceInSeconds */
    public function testDifferenceInSeconds(int $expectedDifference, \DateTime $timeOne, \DateTime $timeTwo): void
    {
        $this->assertEquals($expectedDifference, TimeHelper::differenceInSeconds($timeOne, $timeTwo));
    }

    public function dataAddSecondsToTime(): array
    {
        return [
            // Starting from zero
            [new \DateTime('1970-01-01 00:00:00'), new \DateTime('1970-01-01 00:00:00'), 0],
            [new \DateTime('1970-01-01 00:00:15'), new \DateTime('1970-01-01 00:00:00'), 15],
            [new \DateTime('1970-01-01 00:01:00'), new \DateTime('1970-01-01 00:00:00'), 60],
            [new \DateTime('1970-01-01 01:01:01'), new \DateTime('1970-01-01 00:00:00'), 3661],

            // Starting from offset
            [new \DateTime('1970-01-01 05:20:30'), new \DateTime('1970-01-01 05:20:30'), 0],
            [new \DateTime('1970-01-01 05:20:45'), new \DateTime('1970-01-01 05:20:30'), 15],
            [new \DateTime('1970-01-01 05:21:30'), new \DateTime('1970-01-01 05:20:30'), 60],
            [new \DateTime('1970-01-01 06:21:31'), new \DateTime('1970-01-01 05:20:30'), 3661],

            // Crosses day threshold
            [new \DateTime('1970-01-01 00:00:00'), new \DateTime('1970-01-01 23:59:59'), 1],
            [new \DateTime('1970-01-01 01:59:59'), new \DateTime('1970-01-01 23:59:59'), 7200],
        ];
    }

    /** @dataProvider dataAddSecondsToTime */
    public function testAddSecondsToTime(\DateTime $expectedTime, \DateTime $time, int $secondsToAdd): void
    {
        $this->assertEquals($expectedTime, TimeHelper::addSecondsToTime($time, $secondsToAdd));
    }
}