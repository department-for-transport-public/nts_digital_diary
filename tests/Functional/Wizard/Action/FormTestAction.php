<?php

namespace App\Tests\Functional\Wizard\Action;

use App\Tests\Functional\Wizard\Form\AbstractFormTestCase;

class FormTestAction extends AbstractFormTestAction
{
    protected ?string $submitButtonId;

    /** @var AbstractFormTestCase[]|array */
    protected array $formTestCases;

    public function __construct(string $expectedPath, string $submitButtonId = null, array $formTestCases = [], array $options = [])
    {
        parent::__construct($expectedPath, $submitButtonId, $options);
        $this->formTestCases = $formTestCases;
    }

    public function getSubmitButtonId(): ?string
    {
        return $this->submitButtonId;
    }

    /**
     * @return AbstractFormTestCase[]|array
     */
    public function getFormTestCases(): array
    {
        return $this->formTestCases;
    }

    public function perform(Context $context): void
    {
        $this->performFormTestAction($context, $this->getFormTestCases());
    }
}