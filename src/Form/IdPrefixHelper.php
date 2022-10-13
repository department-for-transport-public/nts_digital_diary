<?php

namespace App\Form;

class IdPrefixHelper
{
    protected array $fieldPrefixes;

    public function __construct(array $fieldPrefixes)
    {
        $this->fieldPrefixes = $fieldPrefixes;
    }

    public function isRelevantForm(array $forms): bool
    {
        foreach ($forms as $name => $form) {
            if ($this->getRelevantPrefix($name)) {
                return true;
            }
        }
        return false;
    }

    public function getIdFromFormName(string $formName, string $fieldPrefix = null): ?string
    {
        $fieldPrefix = $fieldPrefix ?? $this->getRelevantPrefix($formName);

        return $fieldPrefix ?
            substr($formName, strlen($fieldPrefix) + 1) :
            null;
    }

    public function getRelevantPrefix(string $formName): ?string {
        foreach($this->fieldPrefixes as $fieldPrefix) {
            if (strpos($formName, "{$fieldPrefix}-") === 0) {
                return $fieldPrefix;
            }
        }
        return null;
    }
}