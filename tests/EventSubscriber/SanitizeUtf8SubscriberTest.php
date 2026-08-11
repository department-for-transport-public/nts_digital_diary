<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\SanitizeUtf8Subscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SanitizeUtf8SubscriberTest extends TestCase
{
    private LoggerInterface $logger;
    private SanitizeUtf8Subscriber $subscriber;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new SanitizeUtf8Subscriber($this->logger);
    }

    private function createRequestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, $requestType);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = SanitizeUtf8Subscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertSame(['onKernelRequest', 200], $events[KernelEvents::REQUEST]);
    }

    public function testValidUtf8PostPassesThrough(): void
    {
        $request = Request::create('/feedback', 'POST', ['message' => 'Hello, valid UTF-8!']);
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertSame('Hello, valid UTF-8!', $request->request->get('message'));
    }

    public function testEmptyRequestPassesThrough(): void
    {
        $request = Request::create('/feedback', 'GET');
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testInvalidUtf8InPostBodyIsNulled(): void
    {
        $request = Request::create('/feedback', 'POST', [
            'message' => "hello\x80\x81world",
            'email' => 'valid@example.com',
        ]);
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertNull($request->request->get('message'));
        $this->assertSame('valid@example.com', $request->request->get('email'));
    }

    public function testInvalidUtf8InQueryStringIsNulled(): void
    {
        $request = Request::create('/feedback', 'GET', ['info' => "data\xc3\x28"]);
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertNull($request->query->get('info'));
    }

    public function testInvalidUtf8InNestedArrayIsNulled(): void
    {
        $request = Request::create('/feedback', 'POST', [
            'form' => [
                'username' => 'valid',
                'firstName' => "J\x80ohn",
            ],
        ]);
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());

        $form = $request->request->all('form');
        $this->assertSame('valid', $form['username']);
        $this->assertNull($form['firstName']);
    }

    public function testInvalidUtf8InJsonRawContentReturns400(): void
    {
        $request = Request::create('/api/endpoint', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], "{\x80\"key\": \"value\"}");
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $event->getResponse()->getStatusCode());
    }

    public function testValidUtf8InJsonRawContentPassesThrough(): void
    {
        $request = Request::create('/api/endpoint', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"key": "valid UTF-8 value"}');
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testFormPostWithInvalidUtf8InRawBodyIsSanitizedNotRejected(): void
    {
        $request = Request::create('/feedback', 'POST', [
            'message' => "hello\x80world",
        ]);
        // Request::create sets Content-Type to application/x-www-form-urlencoded for POST
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertNull($request->request->get('message'));
    }

    public function testSubRequestIsIgnored(): void
    {
        $request = Request::create('/feedback', 'POST', ['message' => "hello\x80world"]);
        $event = $this->createRequestEvent($request, HttpKernelInterface::SUB_REQUEST);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertSame("hello\x80world", $request->request->get('message'));
    }

    public function testLogsWarningOnSanitization(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Sanitized request parameters containing invalid UTF-8',
                $this->callback(function (array $context): bool {
                    return $context['method'] === 'POST'
                        && str_contains($context['uri'], '/feedback')
                        && array_key_exists('ip', $context);
                })
            );

        $request = Request::create('/feedback', 'POST', ['message' => "bad\x80data"]);
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);
    }

    public function testNonStringValuesAreIgnored(): void
    {
        $request = Request::create('/feedback', 'POST', [
            'count' => 42,
            'enabled' => true,
            'nothing' => null,
        ]);
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testValidMultibyteUtf8Unchanged(): void
    {
        $value = "Sch\u{00f6}ne Gr\u{00fc}\u{00df}e aus M\u{00fc}nchen \u{1f600}";
        $request = Request::create('/feedback', 'POST', ['message' => $value]);
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertSame($value, $request->request->get('message'));
    }
}
