<?php

namespace App\Tests\Functional\Wizard;

class SummaryRowTestCase
{
    public function __construct(protected string $key, protected string $value, protected ?string $keyParams = null, protected ?string $valueParams = null)
    {
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getKeyParams(): ?string {
        return $this->keyParams;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function getValueParams(): ?string {
        return $this->valueParams;
    }
}