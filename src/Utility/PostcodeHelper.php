<?php

namespace App\Utility;

class PostcodeHelper
{
    public static function getRegex(bool $optionalSecondPart=false): string
    {
        return '/^([A-Z]{1,2}\d[A-Z0-9]?) ?(\d[A-Z]{2})'.($optionalSecondPart ? '?' : '').'$/i';
    }

    public static function isValidPostcode(string $potentialPostcode): bool
    {
        return (bool) preg_match(self::getRegex(), $potentialPostcode);
    }

    public static function formatIfPostcode(?string $potentialPostcode, bool $optionalSecondPart=false): ?string
    {
        if ($potentialPostcode === null) {
            return null;
        }

        return preg_match(self::getRegex($optionalSecondPart), $potentialPostcode, $matches) ?
            strtoupper(trim($matches[1]. ' '.($matches[2] ?? ''))) :
            $potentialPostcode;
    }
}