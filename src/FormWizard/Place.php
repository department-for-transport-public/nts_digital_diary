<?php


namespace App\FormWizard;


class Place
{
    public string $name;
    public array $context;

    public function __construct(string $name, array $context = [])
    {
        $this->name = $name;
        $this->context = $context;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getContextValue($key, $defaultValue = null)
    {
        return $this->context[$key] ?? $defaultValue;
    }

    public function isSameAs(?Place $place): bool {
        return
            $place !== null
            && $this->name === $place->name
            && $this->context === $place->context;
    }
}