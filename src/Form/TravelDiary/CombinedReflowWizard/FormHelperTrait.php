<?php

namespace App\Form\TravelDiary\CombinedReflowWizard;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use Symfony\Component\Translation\TranslatableMessage;

trait FormHelperTrait
{
    protected function getMethodsList(Journey $journey, bool $reverse = false): TranslatableMessage
    {
        $stages = $journey->getStages();
        $keys = implode(", ", $stages->map(fn(Stage $s) => "{{$s->getNumber()}}")->toArray());
        $translations = array_combine($stages->map(fn(Stage $s) => "{{$s->getNumber()}}")->toArray(), $stages->map(fn(Stage $s) => $s->getMethodForDisplay())->toArray());
        if ($reverse) {
            $translations = array_reverse($translations);
        }
        return new TranslatableMessage("{$keys}", $translations, 'travel-diary');
    }
}