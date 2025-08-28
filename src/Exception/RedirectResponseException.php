<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectResponseException extends \RuntimeException
{
    public function __construct(protected readonly RedirectResponse $redirectResponse, $message = '', $code = 0, \Exception $previousException = null) {
        parent::__construct($message, $code, $previousException);
    }

    public function getRedirectResponse(): RedirectResponse
    {
        return $this->redirectResponse;
    }
}