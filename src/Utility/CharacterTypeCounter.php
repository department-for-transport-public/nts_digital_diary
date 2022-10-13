<?php

namespace App\Utility;

class CharacterTypeCounter
{
    public static function getCharacterTypeCounts($value): array
    {
        return [
            'upper' => self::countCharactersOfType($value, ':: [:Upper:] Remove;'),
            'lower' => self::countCharactersOfType($value, ':: [:Lower:] Remove;'),
            'number' => self::countCharactersOfType($value, ':: [:Number:] Remove;'),
            'symbol' => self::countCharactersOfType($value, ':: [[:Upper:][:Lower:][:Number:]] Remove;', true),
        ];
    }

    protected static function countCharactersOfType(string $string, string $rule, bool $remainderOnly=false): int
    {
        $normalised = (\Transliterator::createFromRules($rule))->transliterate($string);

        return $remainderOnly ?
            mb_strlen($normalised) :
            mb_strlen($string) - mb_strlen($normalised);
    }
}