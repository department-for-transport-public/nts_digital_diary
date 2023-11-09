<?php

namespace App\Serializer\ExportApi;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class ErrorNormalizer implements ContextAwareNormalizerInterface
{
    private $defaultContext = [
        'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
        'title' => 'An error occurred',
    ];

    public function __construct(protected RequestStack $requestStack) {}

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return
            $format === 'json'
            && isset($context['excpetion'])
            && $context['exception'] instanceof HttpExceptionInterface
            && str_starts_with($request->getPathInfo(), '/api/v1/')
            && !($context['debug'] ?? true)
            ;
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