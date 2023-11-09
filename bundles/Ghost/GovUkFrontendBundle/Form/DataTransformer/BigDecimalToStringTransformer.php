<?php

namespace Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\RoundingNecessaryException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BigDecimalToStringTransformer implements DataTransformerInterface
{
    public function __construct(
        protected int $scale,
        protected string $invalidMessage,
        protected array $invalidMessageParameters = [],
        protected bool $trimTrailingDecimalZeroes = true
    ) {}

    public function transform($value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof BigDecimal) {
            throw new TransformationFailedException('Invalid decimal amount', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        $value = strval($value);

        if ($this->trimTrailingDecimalZeroes && str_contains($value, '.')) {
            $value = rtrim($value, '0');
            $value = rtrim($value, '.');
        }

        return $value;
    }

    public function reverseTransform($value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $decimal = BigDecimal::of($value);
        } catch (MathException) {
            throw new TransformationFailedException('Invalid decimal string', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        // Two possibilities:
        // a) Decimal will be successfully converted to the required scale.
        // b) Decimal will be too long to convert to the required scale, and we will leave a validator to flag it.

        // Examples:
        // 3.0, 2dp    = success, 3.00 outputted (and potentially written to database)
        // 3.0123, 2dp = failure, 3.0123 outputted and flagged by validator on form
        // 3, 2dp      = success, 3.00 outputted

        // In summary:
        // * If scale not possible:
        //   - We don't trigger an exception - we don't have much control over the errors from here.
        //   - We leave it for a validator to flag and provide a friendly error message for.
        // * If scale possible:
        //   - We convert to scale here to give consistent values (e.g. 3, 3.0, 3.00 @ 2dp -> 3.00)
        //   - This means that things like Doctrine do not try to act upon non-changes

        try {
            $decimal = $decimal->toScale($this->scale);
        } catch (RoundingNecessaryException) {}

        return $decimal;
    }
}
