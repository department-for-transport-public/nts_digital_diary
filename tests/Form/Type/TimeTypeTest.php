<?php

namespace App\Tests\Form\Type;

use App\Form\OldGhostBundle\TimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class TimeTypeTest extends AbstractTypeTest
{
    public function dataOptions(): array
    {
        return [
            [
                [],
                MissingOptionsException::class
            ],
            [
                ['mode' => TimeType::MODE_12_HOUR],
                null
            ],
            [
                ['mode' => TimeType::MODE_24_HOUR],
                null
            ],
            [
                ['mode' => '36-hour'],
                InvalidOptionsException::class
            ],
        ];
    }

    /**
     * @dataProvider dataOptions
     */
    public function testOptions(array $options, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $form = $this->factory->create(TimeType::class, null, $options);

        // This test is so that we don't end up with a test with no assertions when an exception is not expected
        $this->assertInstanceOf(FormInterface::class, $form);
    }

    public function dataSubmit(): array
    {
        $hour12 = ['mode' => TimeType::MODE_12_HOUR];
        $hour24 = ['mode' => TimeType::MODE_24_HOUR];

        return [
            // 12 hour mode
            // ==================================================

            // Form should not contain extra fields
            [['banana' => '123'], $hour12, false],
            [['am_or_pm' => ['banana' => '123']], $hour12, false],

            // Invalid choices
            [['am_or_pm' => 'zz'], $hour12, false],

            // Invalid values (incomplete)
            [['am_or_pm' => 'am'], $hour12, false],
            [['am_or_pm' => 'pm'], $hour12, false],

            [['am_or_pm' => 'pm', 'hour' => 1], $hour12, false],
            [['am_or_pm' => 'pm', 'minute' => 1], $hour12, false],
            [['hour' => 1, 'minute' => 1], $hour12, false],

            // Valid (but empty) form values
            [[], $hour12, true, null],
            [['am_or_pm' => null], $hour12, true, null],
            [['am_or_pm' => null, 'hour' => null, 'minute' => null], $hour12, true, null],

            // Invalid values (out-of-range)
            [['am_or_pm' => 'pm', 'hour' => -1, 'minute' => 1], $hour12, false],
            [['am_or_pm' => 'am', 'hour' => -1, 'minute' => 1], $hour12, false],
            [['am_or_pm' => 'pm', 'hour' => 0, 'minute' => 1], $hour12, false],
            [['am_or_pm' => 'am', 'hour' => 0, 'minute' => 1], $hour12, false],
            [['am_or_pm' => 'pm', 'hour' => 13, 'minute' => 1], $hour12, false],
            [['am_or_pm' => 'am', 'hour' => 13, 'minute' => 1], $hour12, false],

            [['am_or_pm' => 'pm', 'hour' => 1, 'minute' => -1], $hour12, false],
            [['am_or_pm' => 'am', 'hour' => 1, 'minute' => -1], $hour12, false],
            [['am_or_pm' => 'pm', 'hour' => 1, 'minute' => 60], $hour12, false],
            [['am_or_pm' => 'am', 'hour' => 1, 'minute' => 60], $hour12, false],

            // Valid values
            [['am_or_pm' => 'pm', 'hour' => 12, 'minute' => 0], $hour12, true, new \DateTime('1970-01-01 12:00:00')],
            [['am_or_pm' => 'am', 'hour' => 12, 'minute' => 0], $hour12, true, new \DateTime('1970-01-01 00:00:00')],
            [['am_or_pm' => 'pm', 'hour' => 1, 'minute' => 0], $hour12, true, new \DateTime('1970-01-01 13:00:00')],
            [['am_or_pm' => 'am', 'hour' => 1, 'minute' => 0], $hour12, true, new \DateTime('1970-01-01 01:00:00')],

            [['am_or_pm' => 'pm', 'hour' => 4, 'minute' => 59], $hour12, true, new \DateTime('1970-01-01 16:59:00')],
            [['am_or_pm' => 'am', 'hour' => 4, 'minute' => 59], $hour12, true, new \DateTime('1970-01-01 04:59:00')],

            // 24 hour mode
            // ==================================================

            // Form should not contain extra fields
            [['banana' => '123'], $hour24, false],

            // Invalid values (incomplete)
            [['hour' => 1], $hour24, false],
            [['minute' => 1], $hour24, false],

            // Valid (but empty) form values
            [[], $hour24, true, null],
            [['hour' => null], $hour24, true, null],
            [['minute' => null], $hour24, true, null],
            [['hour' => null, 'minute' => null], $hour24, true, null],

            // Invalid values (out-of-range)
            [['hour' => -1, 'minute' => 1], $hour24, false],
            [['hour' => -1, 'minute' => 1], $hour24, false],
            [['hour' => 24, 'minute' => 1], $hour24, false],
            [['hour' => 24, 'minute' => 1], $hour24, false],

            [['hour' => 1, 'minute' => -1], $hour24, false],
            [['hour' => 1, 'minute' => -1], $hour24, false],
            [['hour' => 1, 'minute' => 60], $hour24, false],
            [['hour' => 1, 'minute' => 60], $hour24, false],

            // Valid values
            [['hour' => 0, 'minute' => 0], $hour24, true, new \DateTime('1970-01-01 00:00:00')],
            [['hour' => 12, 'minute' => 0], $hour24, true, new \DateTime('1970-01-01 12:00:00')],
            [['hour' => 1, 'minute' => 0], $hour24, true, new \DateTime('1970-01-01 01:00:00')],
            [['hour' => 13, 'minute' => 0], $hour24, true, new \DateTime('1970-01-01 13:00:00')],

            [['hour' => 4, 'minute' => 59], $hour24, true, new \DateTime('1970-01-01 04:59:00')],
            [['hour' => 16, 'minute' => 59], $hour24, true, new \DateTime('1970-01-01 16:59:00')],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, array $options, bool $expectedValid, ?\DateTime $expectedData=null): void
    {
        $form = $this->factory->create(TimeType::class, null, $options);

        $form->submit($formData);

        if ($expectedValid) {
            $this->assertTrue($form->isSynchronized());
            $this->assertTrue($form->isValid());
            $this->assertEquals($expectedData, $form->getData());
        } else {
            $isSynchronisedAndValid = $form->isSynchronized() && $form->isValid();
            $this->assertFalse($isSynchronisedAndValid);
        }
    }
}
