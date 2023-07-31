<?php

namespace App\FormWizard;

interface MultipleEntityInterface
{
    public function getEntitiesToPersist(): array;
}