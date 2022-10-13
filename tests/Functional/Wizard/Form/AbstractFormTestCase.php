<?php

namespace App\Tests\Functional\Wizard\Form;

use App\Tests\Functional\Wizard\Action\Context;

abstract class AbstractFormTestCase
{
    protected array $expectedErrorIds;
    protected ?string $submitButtonId;
    protected bool $skipPageUrlChangeCheck;

    public function __construct(array $expectedErrorIds = [], string $submitButtonId = null, $skipPageUrlChangeCheck = false)
    {
        $this->expectedErrorIds = $expectedErrorIds;
        $this->submitButtonId = $submitButtonId;
        $this->skipPageUrlChangeCheck = $skipPageUrlChangeCheck;
    }

    public function getExpectedErrorIds(): array
    {
        return $this->expectedErrorIds;
    }

    public function getSubmitButtonId(): ?string
    {
        return $this->submitButtonId;
    }

    public function getSkipPageUrlChangeCheck()
    {
        return $this->skipPageUrlChangeCheck;
    }

    abstract public function getFormData(Context $context): array;
}