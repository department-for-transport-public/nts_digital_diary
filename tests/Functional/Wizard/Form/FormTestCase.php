<?php

namespace App\Tests\Functional\Wizard\Form;

use App\Tests\Functional\Wizard\Action\Context;

class FormTestCase extends AbstractFormTestCase
{
    public function __construct(protected array $formData, array $expectedErrorIds = [], string $submitButtonId = null, bool $skipPageUrlChangeCheck = false)
    {
        parent::__construct($expectedErrorIds, $submitButtonId, $skipPageUrlChangeCheck);
    }

    public function getFormData(Context $context): array
    {
        return $this->formData;
    }
}