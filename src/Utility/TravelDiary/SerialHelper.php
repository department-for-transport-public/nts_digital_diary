<?php

namespace App\Utility\TravelDiary;

class SerialHelper
{
    private const CHECK_LETTERS = "ABCDEFGHJKLMNPQRSTVWXYZ";

    public static function getCheckLetter(int $area, int $addressNumber, int $householdNumber): string
    {
        $serial = intval(sprintf("%06d%02d%01d", $area, $addressNumber, $householdNumber));
        return substr(self::CHECK_LETTERS, $serial % 23, 1);
    }
}