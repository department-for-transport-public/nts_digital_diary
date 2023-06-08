<?php

namespace App\Form;

use Symfony\Component\Form\FormInterface;

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

    public function getIdFromForm(FormInterface $form): ?string
    {
        // check current form name for an ID and return it if it has one
        if (preg_match('/-([0-7][0-9A-HJKMNP-TV-Z]{25})$/', $form->getName(), $matches)) {
            return $matches[1];
        }

        // call again with parent form (unless root)
        if ($form->isRoot()) {
            return null;
        }

        return $this->getIdFromForm($form->getParent());
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
            if (strpos($formName, "{$fieldPrefix}") === 0) {
                return $fieldPrefix;
            }
        }
        return null;
    }
}