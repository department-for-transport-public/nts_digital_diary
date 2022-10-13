<?php

namespace App\Tests\Utility\TravelDiary;

use App\Utility\TravelDiary\SerialHelper;
use PHPUnit\Framework\TestCase;

class SerialHelperTest extends TestCase
{
    public function dataCheckLetters(): array
    {
        return [
            [220427, 15, 1, 'E'],
            [220418, 14, 1, 'L'],
            [200120, 7, 1, 'R'],
        ];
    }

    /**
     * @dataProvider dataCheckLetters
     */
    public function testCheckLetter(int $area, int $addressNumber, int $householdNumber, $expectedCheckLetter): void
    {
        $this->assertSame($expectedCheckLetter, SerialHelper::getCheckLetter($area, $addressNumber, $householdNumber));
    }
}