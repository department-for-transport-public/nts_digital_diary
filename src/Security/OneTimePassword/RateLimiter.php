<?php

namespace App\Security\OneTimePassword;

use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Security;

class RateLimiter extends AbstractRequestRateLimiter
{
    public function __construct(
        private readonly RateLimiterFactory $globalFactory,
        private readonly RateLimiterFactory $localFactory,
    ) {}

    protected function getLimiters(Request $request): array
    {
        if ($request->attributes->get(FormAuthenticator::TRAINING_INTERVIEWER_SIG_VERIFIED_REQUEST_KEY, false)) {
            return [];
        }

        $username = $request->attributes->get(Security::LAST_USERNAME);
        $username = preg_match('//u', $username) ? mb_strtolower($username, 'UTF-8') : strtolower($username);

        return [
            $this->globalFactory->create($request->getClientIp()),
            $this->localFactory->create($username.'-'.$request->getClientIp()),
        ];
    }
}