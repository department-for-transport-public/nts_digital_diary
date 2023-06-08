<?php

namespace App\Utility;

use Symfony\Component\Translation\TranslatableMessage;

class VimeoVideo
{
    public string $id;
    public string|TranslatableMessage $title;

    public function __construct(string $id, string|TranslatableMessage $title)
    {
        $this->id = $id;
        $this->title = $title;
    }
}