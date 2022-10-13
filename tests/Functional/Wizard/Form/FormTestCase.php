<?php

namespace App\Tests\Functional\Wizard\Form;

use App\Tests\Functional\Wizard\Action\Context;

class FormTestCase extends AbstractFormTestCase
{
    protected array $formData;

    public function __construct(array $formData, array $expectedErrorIds = [], string $submitButtonId = null, $skipPageUrlChangeCheck = false)
    {
        $this->formData = $formData;
        parent::__construct($expectedErrorIds, $submitButtonId, $skipPageUrlChangeCheck);
    }

    public function getFormData(Context $context): array
    {
        return $this->formData;
    }
}