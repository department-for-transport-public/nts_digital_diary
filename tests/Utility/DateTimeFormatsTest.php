<?php

namespace App\Tests\Utility;

use App\Entity\UserPreferences;
use App\Utility\DateTimeFormats;
use App\Utility\TravelDiary\UserPreferencesHelper;
use PHPUnit\Framework\TestCase;

class DateTimeFormatsTest extends TestCase
{
    public function dataGetTime(): array
    {
        return [
            ['g:i a', DateTimeFormats::MODE_12_HOUR, DateTimeFormats::MODE_12_HOUR],
            ['H:i',  DateTimeFormats::MODE_24_HOUR, DateTimeFormats::MODE_12_HOUR],
            ['g:i a', 'user-pref',                   DateTimeFormats::MODE_12_HOUR],

            ['g:i a', DateTimeFormats::MODE_12_HOUR, DateTimeFormats::MODE_24_HOUR],
            ['H:i',  DateTimeFormats::MODE_24_HOUR, DateTimeFormats::MODE_24_HOUR],
            ['H:i', 'user-pref',                    DateTimeFormats::MODE_24_HOUR],
        ];
    }

    /**
     * @dataProvider dataGetTime
     */
    public function testGetTime(string $expectedTime, string $type, ?string $timeFormat): void
    {
        $dateTimeFormats = $this->getDateTimeFormats(true, $timeFormat);
        $time = $dateTimeFormats->getTime($type);

        $this->assertEquals($expectedTime, $time);
    }

    public function dataNonPrefUserGetTime(): array
    {
        return [
            ['g:i a', DateTimeFormats::MODE_12_HOUR],
            ['H:i',  DateTimeFormats::MODE_24_HOUR],
            [null,   'user-pref'],

            ['g:i a', DateTimeFormats::MODE_12_HOUR],
            ['H:i',  DateTimeFormats::MODE_24_HOUR],
            [null,   'user-pref'],
        ];
    }

    /**
     * @dataProvider dataNonPrefUserGetTime
     */
    public function testNonPrefUserGetTime(?string $expectedTime, string $type): void
    {
        $dateTimeFormats = $this->getDateTimeFormats(false, null);

        if (!$expectedTime) {
            $this->expectException(\RuntimeException::class);
        }

        $time = $dateTimeFormats->getTime($type);

        if ($expectedTime) {
            $this->assertEquals($expectedTime, $time);
        }
    }

    protected function getDateTimeFormats(bool $hasUserPreferences, ?string $timeFormat): DateTimeFormats
    {
        $prefsHelperMock = $this->createMock(UserPreferencesHelper::class);

        if ($hasUserPreferences) {
            if ($timeFormat === null) {
                $this->fail('Should not set $timeFormat to NULL if $hasUserPreferences is true');
            }

            $userPrefs = (new UserPreferences())
                ->setTimeFormat($timeFormat);
        } else {
            $userPrefs = null;
        }

        $prefsHelperMock
            ->method('getOriginalUserPreferences')
            ->willReturn($userPrefs);

        return new DateTimeFormats($prefsHelperMock);
    }
}
