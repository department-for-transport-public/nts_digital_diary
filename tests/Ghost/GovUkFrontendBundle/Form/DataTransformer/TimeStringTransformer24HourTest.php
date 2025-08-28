<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\DataTransformer;

use App\Form\OldGhostBundle\DataTransformer\TimeStringTransformer;

class TimeStringTransformer24HourTest extends AbstractTimeStringTransformerTest
{
    public function transformData(): array
    {
        $empty = $this->data(null, null);

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
            [new \DateTime('13:45:15'), $this->data('13', '45')],
            [new \DateTime('5:00:13'), $this->data('5', '00')],
            [new \DateTime('00:00:00'), $this->data('0', '00')],
            [new \DateTime('23:59:59'), $this->data('23', '59')],
        ];
    }

    public function reverseTransformData(): array
    {
        return [
            // Empty value
            [$this->data(null, null), null],
            [$this->data('', ''), null],
            [[], null], // A weird one

            // Invalid values
            ['banana', null, true],
            ['123', null, true],
            [123, null, true],
            [-34.5, null, true],

            // Invalid array: missing value
            [$this->data(null, 34), null, true],
            [$this->data(12, null), null, true],

            // Invalid array: out of range
            [$this->data(24, 34), null, true],
            [$this->data(-1, 34), null, true],
            [$this->data(1, 60), null, true],
            [$this->data(1, -1), null, true],

            // Valid
            [$this->data(0, 0), new \DateTime('1970-01-01 00:00:00')],
            [$this->data(1, 35), new \DateTime('1970-01-01 01:35:00')],
            [$this->data(20, 59), new \DateTime('1970-01-01 20:59:00')],
            [$this->data(12, 33), new \DateTime('1970-01-01 12:33:00')],
            [$this->data(0, 33), new \DateTime('1970-01-01 00:33:00')],
        ];
    }

    public function data(mixed $hour, mixed $minute): array {
        return ['hour' => $hour, 'minute' => $minute];
    }

    function getMode(): string
    {
        return TimeStringTransformer::MODE_24_HOUR;
    }
}
