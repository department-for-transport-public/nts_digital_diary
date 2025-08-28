<?php

namespace App\Form\OldGhostBundle\DataTransformer;

use App\Form\OldGhostBundle\TimeType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TimeStringTransformer implements DataTransformerInterface
{
    public const string MODE_12_HOUR = TimeType::MODE_12_HOUR;
    public const string MODE_24_HOUR = TimeType::MODE_24_HOUR;

    public function __construct(protected string $mode) {}

    /**
     * @return array{hour: ?string, minute: ?string, am_or_pm: ?string}
     * @return array{hour: ?string, minute: ?string}
     */
    public function transform(mixed $value): array
    {
        if ($value === null) {
            return match($this->mode) {
                self::MODE_12_HOUR => [
                    'hour' => null,
                    'minute' => null,
                    'am_or_pm' => null,
                ],
                self::MODE_24_HOUR => [
                    'hour' => null,
                    'minute' => null,
                ]
            };
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Invalid value');
        }

        return match($this->mode) {
            self::MODE_12_HOUR => [
                'hour' => $value->format('g'),
                'minute' => $value->format('i'),
                'am_or_pm' => $value->format('a'),
            ],
            self::MODE_24_HOUR => [
                'hour' => $value->format('G'),
                'minute' => $value->format('i'),
            ]
        };
    }

    public function reverseTransform(mixed $value): ?\DateTime
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('Invalid value');
        }

        $hour = $value['hour'] ?? null;
        $minute = $value['minute'] ?? null;
        $amOrPm = $value['am_or_pm'] ?? null;

        if (!is_numeric($hour) && !is_numeric($minute) && empty($amOrPm)) {
            return null;
        }

        $validHours = match($this->mode) {
            self::MODE_12_HOUR => [1,12],
            self::MODE_24_HOUR => [0,23]
        };

        if (null === $hour) {
            throw new TransformationFailedException('Invalid hour value', 0, null, "Enter an hour (" . join('-', $validHours) . ")");
        }

        if (null === $minute) {
            throw new TransformationFailedException('Invalid minute value', 0, null, 'Enter a minutes value (0-59)');
        }

        if ($this->mode === self::MODE_12_HOUR && null === $amOrPm) {
            throw new TransformationFailedException('Invalid am/pm value', 0, null, 'Select "am" or "pm"');
        }

        $hour = intval($hour);
        $minute = intval($minute);

        if ($hour < $validHours[0] || $hour > $validHours[1]) {
            throw new TransformationFailedException('Invalid hour value', 0, null, "Enter a real hour value (" . join('-', $validHours) . ")");
        }

        if ($minute < 0 || $minute > 59) {
            throw new TransformationFailedException('Invalid hour value', 0, null, 'Enter a real minutes value (0-59)');
        }

        if ($this->mode === self::MODE_12_HOUR && !in_array($amOrPm, ['am', 'pm'])) {
            throw new TransformationFailedException('Invalid am/pm value', 0, null, 'Select "am" or "pm"');
        }

        return match($this->mode){
            self::MODE_12_HOUR => new \DateTime(sprintf('1970-01-01 %d:%02d%s', $hour, $minute, $amOrPm)),
            self::MODE_24_HOUR => new \DateTime(sprintf('1970-01-01 %02d:%02d', $hour, $minute)),
        };
    }
}
