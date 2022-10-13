<?php

namespace App\Tests\Utility;

use App\Utility\AreaPeriodHelper;
use PHPUnit\Framework\TestCase;

class AreaPeriodHelperTest extends TestCase
{
    protected function getYearDigit($modifier = 0): int
    {
        $currentYear = (new \DateTime())->format('Y');
        $currentYearDigit = intval(($currentYear)[3]);
        return ($currentYearDigit + $modifier) % 10;
    }

    public function dataGuessYear(): array
    {
        $currentYear = intval((new \DateTime())->format('Y'));
        return [
            ["{$this->getYearDigit()}01500", $currentYear],
            ["{$this->getYearDigit(1)}01500", $currentYear + 1],
            ["{$this->getYearDigit(2)}01500", $currentYear + 2],
            ["{$this->getYearDigit(3)}01500", $currentYear - 7],
            ["{$this->getYearDigit(4)}01500", $currentYear - 6],
            ["{$this->getYearDigit(5)}01500", $currentYear - 5],
            ["{$this->getYearDigit(6)}01500", $currentYear - 4],
            ["{$this->getYearDigit(7)}01500", $currentYear - 3],
            ["{$this->getYearDigit(8)}01500", $currentYear - 2],
            ["{$this->getYearDigit(9)}01500", $currentYear - 1],
        ];
    }

    /**
     * @dataProvider dataGuessYear
     */
    public function testGuessYear($area, $expectedYear)
    {
        $this->assertSame($expectedYear, AreaPeriodHelper::guessYearFromArea($area));
    }
}