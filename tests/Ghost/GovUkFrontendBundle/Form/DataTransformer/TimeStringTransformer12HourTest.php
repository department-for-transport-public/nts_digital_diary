<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\DataTransformer;

use App\Form\OldGhostBundle\DataTransformer\TimeStringTransformer;

class TimeStringTransformer12HourTest extends AbstractTimeStringTransformerTest
{
    public function transformData(): array
    {
        $empty = $this->data(null, null, null);

        return [
            // Empty values
            [null, $empty],

            // Invalid values,
            ['', null, true],
            [false, null, true],
            [0, null, true],
            [[], null, true],
            ['123', null, true],
            [123, null, true],
            [-1.34, null, true],
            [['123'], null, true],

            // Valid values
            [new \DateTime('13:45:15'), $this->data('1', '45', 'pm')],
            [new \DateTime('5:00:13'), $this->data('5', '00', 'am')],
            [new \DateTime('00:00:00'), $this->data('12', '00', 'am')],
        ];
    }

    public function reverseTransformData(): array
    {
        return [
            // Empty value
            [$this->data(null, null, null), null],
            [$this->data('', '', ''), null],
            [[], null], // A weird one

            // Invalid values
            ['banana', null, true],
            ['123', null, true],
            [123, null, true],
            [-34.5, null, true],

            // Invalid array: missing value
            [$this->data(null, 34, 'am'), null, true],
            [$this->data(12, null, 'am'), null, true],
            [$this->data(12, 34, null), null, true],

            // Invalid array: out of range
            [$this->data(13, 34, 'am'), null, true],
            [$this->data(0, 34, 'am'), null, true],
            [$this->data(-1, 34, 'am'), null, true],
            [$this->data(1, 60, 'am'), null, true],
            [$this->data(1, -1, 'am'), null, true],
            [$this->data(1, 35, 'zm'), null, true],

            // Valid
            [$this->data(1, 0, 'am'), new \DateTime('1970-01-01 01:00:00')],
            [$this->data(1, 0, 'pm'), new \DateTime('1970-01-01 13:00:00')],
            [$this->data(1, 35, 'am'), new \DateTime('1970-01-01 1:35:00')],
            [$this->data(8, 59, 'pm'), new \DateTime('1970-01-01 20:59:00')],
            [$this->data(12, 33, 'pm'), new \DateTime('1970-01-01 12:33:00')],
            [$this->data(12, 33, 'am'), new \DateTime('1970-01-01 00:33:00')],
        ];
    }

    public function data(mixed $hour, mixed $minute, mixed $am_or_pm): array {
        return ['hour' => $hour, 'minute' => $minute, 'am_or_pm' => $am_or_pm];
    }

    function getMode(): string
    {
        return TimeStringTransformer::MODE_12_HOUR;
    }
}
