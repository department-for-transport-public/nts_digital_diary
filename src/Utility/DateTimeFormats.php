<?php


namespace App\Utility;


use App\Utility\TravelDiary\UserPreferencesHelper;

class DateTimeFormats
{
    public const MODE_12_HOUR = '12-hour';
    public const MODE_24_HOUR = '24-hour';


    private const DATE_DEFAULT = 'd/m/Y';
    private const DATE_REVERSE = 'Y-m-d';
    private const DATE_SHORT_WITH_DOW = 'D jS M';
    private const DATE_WITH_DOW = 'l jS M';

    private const TIME = [
        self::MODE_12_HOUR => 'g:i a',
        self::MODE_24_HOUR => 'H:i'
    ];

    public function __construct(protected UserPreferencesHelper $userPreferencesHelper) {}

    public const DATE_MODES = [
        'default',
        'reverse',
        'with-dow',
        'short-with-dow',
    ];

    /**
     * @param value-of<static::DATE_MODES> $type
     */
    public function getDate(string $type): string
    {
        return match($type) {
            'default' => static::DATE_DEFAULT,
            'reverse' => static::DATE_REVERSE,
            'with-dow' => static::DATE_WITH_DOW,
            'short-with-dow' => static::DATE_SHORT_WITH_DOW,
        };
    }

    public const TIME_MODES = [
        self::MODE_12_HOUR,
        self::MODE_24_HOUR,
        'user-pref',
    ];

    /**
     * @param value-of<static::TIME_MODES> $type
     */
    public function getTime(string $type): string
    {
        $userPrefs = $this->userPreferencesHelper->getOriginalUserPreferences();

        if ($type === 'user-pref' && $userPrefs === null) {
            throw new \RuntimeException('User type does not have preferences; either explicitly choose a time-format, or implement PreferenceUserInterface');
        }

        return match($type) {
            DateTimeFormats::MODE_12_HOUR,
            DateTimeFormats::MODE_24_HOUR
                => DateTimeFormats::TIME[$type],
            'user-pref'
                => DateTimeFormats::TIME[$userPrefs->getTimeFormat()],
        };
    }

}