<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectResponseException extends \RuntimeException
{
    private RedirectResponse $redirectResponse;

    public function __construct(RedirectResponse $redirectResponse, $message = '', $code = 0, \Exception $previousException = null) {
        $this->redirectResponse = $redirectResponse;
        parent::__construct($message, $code, $previousException);
    }

    public function getRedirectResponse(): RedirectResponse
    {
        return $this->redirectResponse;
    }
}