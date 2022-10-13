<?php

namespace App\Tests\Utility;

use App\Utility\ItemByFrequencyHelper;
use PHPUnit\Framework\TestCase;

class ItemByFrequencyHelperTest extends TestCase
{
    public function dataSimpleExpectedOutput(): array
    {
        return [
            [
                [
                    ['name' => 'Brighton', 'count' => 1],
                ],
                ['Brighton'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 1],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 1],
                    ['name' => 'Littlehampton', 'count' => 1],
                ],
                ['Brighton', 'Chichester', 'Bognor'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 1],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 1],
                    ['name' => 'Littlehampton', 'count' => 2],
                ],
                ['Littlehampton', 'Brighton', 'Chichester'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 1],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 2],
                    ['name' => 'Littlehampton', 'count' => 2],
                ],
                ['Bognor', 'Littlehampton', 'Brighton'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 2],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 2],
                    ['name' => 'Littlehampton', 'count' => 3],
                ],
                ['Littlehampton', 'Brighton', 'Bognor'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 2],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 2],
                    ['name' => 'Littlehampton', 'count' => 3],
                    ['name' => 'Havant', 'count' => 1],
                    ['name' => 'Worthing', 'count' => 1],
                    ['name' => 'Emsworth', 'count' => 1],
                ],
                ['Littlehampton', 'Brighton', 'Bognor'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 2],
                    ['name' => 'Chichester', 'count' => 2],
                    ['name' => 'Bognor', 'count' => 2],
                    ['name' => 'Littlehampton', 'count' => 3],
                    ['name' => 'Havant', 'count' => 1],
                    ['name' => 'Worthing', 'count' => 1],
                    ['name' => 'Emsworth', 'count' => 1],
                ],
                ['Littlehampton', 'Brighton', 'Chichester'],
            ],
        ];
    }

    /**
     * @dataProvider dataSimpleExpectedOutput
     */
    public function testSimpleExpectedOutput(array $input, array $expectedOutput): void
    {
        $helper = new ItemByFrequencyHelper();
        $helper->addEntries($input, 'name', 'count');

        $this->assertEquals($expectedOutput, $helper->getTopN(3));
    }

    public function dataTypoExpectedOutput(): array
    {
        return [
            [
                [
                    ['name' => 'Brighton', 'count' => 1],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 1],
                    ['name' => 'Littlehampton', 'count' => 2],
                ],
                ['Littlehampton', 'Brighton', 'Chichester'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 1],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 1],
                    ['name' => 'bognor', 'count' => 1],
                    ['name' => 'Littlehampton', 'count' => 2],
                ],
                ['Bognor', 'Littlehampton', 'Brighton'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 2],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 2],
                    ['name' => 'Littlehampton', 'count' => 1],
                    ['name' => 'littlehampton', 'count' => 1],
                    ['name' => 'little-hampton', 'count' => 1],
                ],
                ['Littlehampton', 'Brighton', 'Bognor'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 1],
                    ['name' => 'brig h-ton', 'count' => 1],
                    ['name' => 'Chichester', 'count' => 1],
                    ['name' => 'Bognor', 'count' => 2],
                    ['name' => 'Littlehampton', 'count' => 2],
                    ['name' => 'li:TTle*hampton', 'count' => 1],
                    ['name' => 'Havant', 'count' => 1],
                    ['name' => 'Worthing', 'count' => 1],
                    ['name' => 'Emsworth', 'count' => 1],
                ],
                ['Littlehampton', 'Brighton', 'Bognor'],
            ],
            [
                [
                    ['name' => 'Brighton', 'count' => 2],
                    ['name' => 'Chichester', 'count' => 2],
                    ['name' => 'Bognor', 'count' => 2],
                    ['name' => 'Littlehampton75', 'count' => 1],
                    ['name' => 'litt le%$hampton75', 'count' => 1],
                    ['name' => 'litt(le)h&^ampton7!!5', 'count' => 1],
                    ['name' => 'Havant', 'count' => 1],
                    ['name' => 'Worthing', 'count' => 1],
                    ['name' => 'Emsworth', 'count' => 1],
                ],
                ['Littlehampton75', 'Brighton', 'Chichester'],
            ],
        ];
    }

    /**
     * @dataProvider dataTypoExpectedOutput
     */
    public function testTypoExpectedOutput(array $input, array $expectedOutput): void
    {
        $helper = new ItemByFrequencyHelper();
        $helper->addEntries($input, 'name', 'count');

        $this->assertEquals($expectedOutput, $helper->getTopN(3));
    }
}