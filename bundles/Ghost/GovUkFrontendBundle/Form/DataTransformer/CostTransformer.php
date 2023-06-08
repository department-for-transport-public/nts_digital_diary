<?php

namespace Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CostTransformer implements DataTransformerInterface
{
    /** @var int */
    protected $decimalPlaces;

    /** @var int */
    protected $divisor;

    /** @var string */
    protected $invalidMessage;

    /** @var array */
    protected $invalidMessageParameters;

    public function __construct(int $divisor = 100, string $invalidMessage = 'common.cost.invalid', array $invalidMessageParameters = [])
    {
        $this->divisor = $divisor;
        $this->decimalPlaces = (int) log10($divisor);
        $this->invalidMessage = $invalidMessage;
        $this->invalidMessageParameters = $invalidMessageParameters;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!preg_match('#^-?\d+$#', $value)) {
            throw new TransformationFailedException('Invalid currency amount', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        $value = (int) $value;

        $whole = intdiv($value, $this->divisor);
        $fractional = (string) abs($value % $this->divisor);
        $fractional = str_pad($fractional, $this->decimalPlaces, '0', STR_PAD_LEFT);

        return $this->decimalPlaces > 0 ?
            "{$whole}.{$fractional}" : $whole;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function reverseTransform($value)
    {
        if ('' === $value) {
            return null;
        }

        if (!preg_match('#^(?P<int>-?\d+)(?:\.(?P<dec>\d{'.$this->decimalPlaces.'}))?$#', $value, $matches)) {
            throw new TransformationFailedException('Invalid currency amount', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        $int = $matches['int'];
        $dec = $matches['dec'] ?? null;

        return intval($int.($dec ?? str_repeat('0', $this->decimalPlaces)));
    }
}
