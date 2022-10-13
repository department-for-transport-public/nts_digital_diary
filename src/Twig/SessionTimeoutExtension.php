<?php

namespace App\Twig;

use App\Utility\SessionTimeoutHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SessionTimeoutExtension extends AbstractExtension
{
    protected SessionTimeoutHelper $sessionTimeoutHelper;

    public function __construct(SessionTimeoutHelper $sessionTimeoutHelper)
    {
        $this->sessionTimeoutHelper = $sessionTimeoutHelper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sessionExpiryTime', [$this->sessionTimeoutHelper, 'getExpiryTime']),
            new TwigFunction('sessionWarningTime', [$this->sessionTimeoutHelper, 'getWarningTime']),
        ];
    }
}
