<?php

namespace App\Limiter;

use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class ApiRequestLimiter extends AbstractRequestRateLimiter
{
    private RateLimiterFactory $apiKeyLimiterFactory;
    private RateLimiterFactory $apiIpLimiterFactory;

    public function __construct(RateLimiterFactory $apiKeyLimiter, RateLimiterFactory $apiIpLimiter)
    {
        $this->apiKeyLimiterFactory = $apiKeyLimiter;
        $this->apiIpLimiterFactory = $apiIpLimiter;
    }

    protected function getLimiters(Request $request): array
    {
        $apiKey = $request->headers->get('X-AUTH-KEY');

        $limiters = [
            $this->apiIpLimiterFactory->create($request->getClientIp()),
        ];

        if ($apiKey) {
            $limiters[] = $this->apiKeyLimiterFactory->create($apiKey);
        }

        return $limiters;
    }
}