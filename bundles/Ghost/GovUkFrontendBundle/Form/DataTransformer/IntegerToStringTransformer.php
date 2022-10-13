<?php

namespace Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Symfony's IntegerToLocalizedStringTransformer is *too* clever; I don't want inputs such as "10e8" to be valid input.
 * This transformer only understands integer numbers with an optional "-" prefix.
 */
class IntegerToStringTransformer implements DataTransformerInterface
{
    /** @var string */
    protected $invalidMessage;

    /** @var array */
    protected $invalidMessageParameters;

    public function __construct(string $invalidMessage, array $invalidMessageParameters = [])
    {
        $this->invalidMessage = $invalidMessage;
        $this->invalidMessageParameters = $invalidMessageParameters;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function transform($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!preg_match('#^-?\d+$#', $value, $matches)) {
            throw new TransformationFailedException('Invalid integer amount', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        return (string) $value;
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    public function reverseTransform($value)
    {
        if ('' === trim($value)) {
            return null;
        }

        if (!preg_match('#^-?\d+$#', $value, $matches)) {
            throw new TransformationFailedException('Invalid integer amount', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        return intval($value);
    }
}
