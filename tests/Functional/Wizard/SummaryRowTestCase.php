<?php

namespace App\Tests\Functional\Wizard;

class SummaryRowTestCase
{
    protected string $key;
    protected ?string $keyParams;
    protected string $value;
    protected ?string $valueParams;

    public function __construct(string $key, string $value, ?string $keyParams = null, ?string $valueParams = null) {
        $this->key = $key;
        $this->keyParams = $keyParams;
        $this->value = $value;
        $this->valueParams = $valueParams;
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