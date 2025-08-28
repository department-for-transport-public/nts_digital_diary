<?php

namespace App\Serializer\ExportApi;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ErrorNormalizer implements NormalizerInterface
{
    private array $defaultContext = [
        'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
        'title' => 'An error occurred',
    ];

    public function __construct(protected RequestStack $requestStack) {}

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return
            $format === 'json'
            && isset($context['exception'])
            && $context['exception'] instanceof HttpExceptionInterface
            && str_starts_with($request->getPathInfo(), '/api/v1/')
            && !($context['debug'] ?? true)
            ;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        $context += $this->defaultContext;
        return [
            'type' => $context['type'],
            'title' => $object->getStatusText(),
            'status' => $context['status'] ?? $object->getStatusCode(),
            'detail' => $object->getMessage(),
        ];
    }
}