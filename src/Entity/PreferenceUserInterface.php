<?php

namespace App\Entity;

interface PreferenceUserInterface
{
    public function getPreferences(): UserPreferences;
}