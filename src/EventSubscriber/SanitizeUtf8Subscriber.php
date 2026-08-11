<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SanitizeUtf8Subscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 200],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Sanitize query and POST parameter bags: null out any value containing invalid UTF-8.
        // This preserves valid sibling fields while ensuring garbage can't reach the ICU
        // MessageFormatter (which validates ALL arguments for UTF-8, even unused ones).
        $querySanitized = $this->sanitize($request->query->all());
        $requestSanitized = $this->sanitize($request->request->all());

        $sanitized = ($querySanitized !== null) || ($requestSanitized !== null);

        if ($querySanitized !== null) {
            $request->query->replace($querySanitized);
        }

        if ($requestSanitized !== null) {
            $request->request->replace($requestSanitized);
        }

        // For non-form content types (e.g. JSON API bodies), the raw content is what gets
        // deserialized — and we can't replace it in-place. Reject with 400, which is standard
        // since JSON requires UTF-8 by spec. Skip this check for form submissions, where the
        // parsed parameter bags (already sanitized above) are what matter.
        $contentType = $request->headers->get('Content-Type', '');
        $isFormSubmission = str_contains($contentType, 'application/x-www-form-urlencoded')
            || str_contains($contentType, 'multipart/form-data');

        $content = $request->getContent();
        if (!$isFormSubmission && $content !== '' && !mb_check_encoding($content, 'UTF-8')) {
            $this->logger->warning('Rejected request with invalid UTF-8 in raw body', [
                'method' => $request->getMethod(),
                'uri' => $request->getRequestUri(),
                'ip' => $request->getClientIp(),
            ]);

            $event->setResponse(new Response('Bad Request', Response::HTTP_BAD_REQUEST));
            return;
        }

        if ($sanitized) {
            $this->logger->warning('Sanitized request parameters containing invalid UTF-8', [
                'method' => $request->getMethod(),
                'uri' => $request->getRequestUri(),
                'ip' => $request->getClientIp(),
            ]);
        }
    }

    /**
     * Recursively walk an array, nulling any string value that contains invalid UTF-8.
     * Returns the sanitized array if any changes were made, or null if no changes were needed.
     */
    private function sanitize(array $data): ?array
    {
        $changed = false;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitizedChild = $this->sanitize($value);
                if ($sanitizedChild !== null) {
                    $data[$key] = $sanitizedChild;
                    $changed = true;
                }
            } elseif (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $data[$key] = null;
                $changed = true;
            }
        }

        return $changed ? $data : null;
    }
}
