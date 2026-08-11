<?php

namespace App\Tests\EventSubscriber\Security;

use App\EventSubscriber\Security\SecureHeadersSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecureHeadersSubscriberTest extends KernelTestCase
{
    private function dispatch(Request $request, Response $response): void
    {
        $event = new ResponseEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        self::getContainer()->get(SecureHeadersSubscriber::class)->kernelResponseEvent($event);
    }

    /**
     * Building the response headers must not start the session. Reading a session
     * flash here would start it — a PdoSessionHandler DB hit in production — on
     * every response, including 404s.
     */
    public function testDoesNotStartSessionWhenBuildingHeaders(): void
    {
        self::bootKernel();

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/anything');
        $request->setSession($session);

        $response = new Response('body');
        $response->headers->set('Content-Security-Policy', "default-src 'none'");

        $this->dispatch($request, $response);

        self::assertFalse($session->isStarted(), 'SecureHeadersSubscriber must not start the session');
    }

    /**
     * Regression: a per-response `csp:add` (as set by DataExportController) must be
     * merged into the Content-Security-Policy header — e.g. the completed export
     * page needs storage.googleapis.com framed despite the locked-down policy.
     */
    public function testCspAddIsMergedIntoHeader(): void
    {
        self::bootKernel();

        $request = Request::create('/admin/data-export/pending/abc');
        $request->attributes->set('csp:add', [['frame-src', 'storage.googleapis.com']]);

        $response = new Response('body');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-src 'self'");

        $this->dispatch($request, $response);

        $csp = $response->headers->get('Content-Security-Policy');
        self::assertStringContainsString('storage.googleapis.com', $csp, 'csp:add was not merged into the CSP header');
        // existing frame-src source is preserved alongside the addition
        self::assertMatchesRegularExpression("/frame-src[^;]*'self'/", $csp);
        self::assertMatchesRegularExpression('/frame-src[^;]*storage\.googleapis\.com/', $csp);
    }
}
