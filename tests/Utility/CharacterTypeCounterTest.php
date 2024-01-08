<?php

namespace App\Tests\Utility;

use App\Utility\Security\CharacterTypeCounter;
use PHPUnit\Framework\TestCase;

class CharacterTypeCounterTest extends TestCase
{
    public function dataCharacterTypeCounter(): array
    {
        return [
            ['abcdeFGHIJ12345!"£$%', 5, 5, 5, 5],
            ['klmnOPQRST6789^&*()-', 6, 4, 4, 6],
            ['opqrstUVWX012=_+[]{}', 4, 6, 3, 7],
            ['uvwxYZABCD34;\'#:@~\\|<', 6, 4, 2, 9],
            ['yzE>?,./€', 1, 2, 0, 6],
            ['', 0, 0, 0, 0],

            ['āàáâãäåæþýñß', 0, 12, 0 ,0],
            ['ØÒÃÆẞÝĎĆĶŊŤ', 11, 0, 0 ,0],
        ];
    }

    /**
     * @dataProvider dataCharacterTypeCounter
     */
    public function testCharacterTypeCounter(string $input, int $expectedUpper, int $expectedLower, int $expectedNumber, int $expectedSymbol): void
    {
        $output = CharacterTypeCounter::getCharacterTypeCounts($input);

        $this->assertEquals($expectedUpper, $output['upper'], "Expected $expectedUpper uppercase characters");
        $this->assertEquals($expectedLower, $output['lower'], "Expected $expectedLower lowercase characters");
        $this->assertEquals($expectedNumber, $output['number'], "Expected $expectedNumber numbers");
        $this->assertEquals($expectedSymbol, $output['symbol'], "Expected $expectedSymbol symbols");
    }
}