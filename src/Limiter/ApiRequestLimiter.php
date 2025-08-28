<?php

namespace App\Limiter;

use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class ApiRequestLimiter extends AbstractRequestRateLimiter
{
    public function __construct(
        protected RateLimiterFactory $apiKeyLimiter,
        protected RateLimiterFactory $apiIpLimiter,
    ) {}

    protected function getLimiters(Request $request): array
    {
        $apiKey = $request->headers->get('X-AUTH-KEY');

        $limiters = [
            $this->apiIpLimiter->create($request->getClientIp()),
        ];

        if ($apiKey) {
            $limiters[] = $this->apiKeyLimiter->create($apiKey);
        }

        return $limiters;
    }
}
