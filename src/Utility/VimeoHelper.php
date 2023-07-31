<?php

namespace App\Utility;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VimeoHelper extends AbstractExtension
{
    protected bool $vimeoUsed;

    public function __construct() {
        $this->vimeoUsed = false;
    }

    public function isVimeoUsed(): bool
    {
        return $this->vimeoUsed;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isVimeoUsed', fn() => $this->vimeoUsed),
            new TwigFunction('setVimeoUsed', function(bool $value) {
                $this->vimeoUsed = $value;
                return null;
            }),
        ];
    }
}