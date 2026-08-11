<?php


namespace App\Utility;


use App\Utility\TravelDiary\UserPreferencesHelper;

class DateTimeFormats
{
    public const string MODE_12_HOUR = '12-hour';
    public const string MODE_24_HOUR = '24-hour';


    public const string DATE_DEFAULT = 'd/m/Y';
    public const string DATE_REVERSE = 'Y-m-d';
    public const string DATE_SHORT_WITH_DOW = 'D jS M';
    public const string DATE_WITH_DOW = 'l jS F';

    private const array TIME = [
        self::MODE_12_HOUR => 'g:i a',
        self::MODE_24_HOUR => 'H:i'
    ];

    public function __construct(protected UserPreferencesHelper $userPreferencesHelper) {}

    public const array DATE_MODES = [
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

    public const array TIME_MODES = [
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