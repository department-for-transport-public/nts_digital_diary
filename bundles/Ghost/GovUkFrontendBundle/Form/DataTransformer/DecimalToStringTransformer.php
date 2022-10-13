<?php

namespace Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DecimalToStringTransformer implements DataTransformerInterface
{
    public const VALIDATION_REGEX = '#^(?P<sign>-)?(?P<int>\d+)(?:\.(?P<dec>\d+))?$#';

    /** @var string */
    protected string $invalidMessage;

    /** @var array */
    protected array $invalidMessageParameters;

    public function __construct(string $invalidMessage, array $invalidMessageParameters = [])
    {
        $this->invalidMessage = $invalidMessage;
        $this->invalidMessageParameters = $invalidMessageParameters;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function transform($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!preg_match(self::VALIDATION_REGEX, $value, $matches)) {
            throw new TransformationFailedException('Invalid decimal string', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        if (false !== strpos($value, '.')) {
            $value = rtrim($value, '0');
            return rtrim($value, '.');
        } else {
            return $value;
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function reverseTransform($value)
    {
        if ('' === trim($value)) {
            return null;
        }

        if (!preg_match(self::VALIDATION_REGEX, $value, $matches)) {
            throw new TransformationFailedException('Invalid decimal amount', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        $sign = ($matches['sign'] ?? null) === '-' ? -1 : 1;
        $int = $sign * $matches['int'];
        $dec = $matches['dec'] ?? '';

        return $dec === ''
            ? $int
            : $int.'.'.$dec;
    }
}
