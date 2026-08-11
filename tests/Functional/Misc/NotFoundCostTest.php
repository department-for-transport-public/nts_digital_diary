<?php

namespace App\Tests\Functional\Misc;

use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A 404 that reaches Symfony must be cheap: no DB queries, and it must not render
 * the viewport monitor (which reads/writes the session and fires the /viewport-size
 * XHR — a PdoSessionHandler write + cookie on every 404).
 */
class NotFoundCostTest extends AbstractFunctionalWebTestCase
{
    public function testNotFoundDoesNotHitDatabase(): void
    {
        $this->initialiseClientAndLoadFixtures([]);

        // Discard schema/fixture-setup queries so we count only the 404 request.
        $this->client->getContainer()->get('doctrine.debug_data_holder')->reset();

        $this->client->enableProfiler();
        $this->client->request('GET', '/no-such-route-' . uniqid());
        $this->assertResponseStatusCodeSame(404);

        $profile = $this->client->getProfile();
        self::assertNotFalse($profile, 'Profiler is not available for this request');

        // Ignore the sqlite platform / schema-introspection queries the test
        // harness issues when it opens the connection (SELECT 'main', sqlite_master,
        // PRAGMA …). These aren't application queries and don't occur on a prod 404.
        $appQueries = [];
        foreach ($profile->getCollector('db')->getQueries() as $queries) {
            foreach ($queries as $q) {
                $sql = trim(preg_replace('/\s+/', ' ', is_array($q) ? ($q['sql'] ?? '') : (string) $q));
                if (preg_match("/sqlite_master|sqlite_sequence|^SELECT 'main'|^PRAGMA/i", $sql)) {
                    continue;
                }
                $appQueries[] = $sql;
            }
        }

        self::assertSame([], $appQueries, 'A 404 must not execute any application database queries');
    }

    /**
     * The branded 404 renders only with debug OFF (debug renders Symfony's own
     * exception page, which is why earlier debug-mode tests missed this). It must
     * not include the viewport monitor — error-base.html.twig empties the
     * `viewportMonitor` block.
     */
    public function testBrandedNotFoundExcludesViewportMonitor(): void
    {
        self::ensureKernelShutdown();
        // Force a fresh Twig compile: debug=false disables auto-reload, so a stale
        // var/cache/test/twig could otherwise mask the template change.
        (new Filesystem())->remove(\dirname(__DIR__, 3) . '/var/cache/test/twig');

        $client = static::createClient(['debug' => false]);
        static::getContainer()->get(DatabaseToolCollection::class)->get()->loadFixtures([]);

        $client->request('GET', '/no-such-route-' . uniqid());
        $response = $client->getResponse();

        self::assertSame(404, $response->getStatusCode());
        $body = (string) $response->getContent();
        // Sanity: we rendered the branded GOV.UK page, not the debug page.
        self::assertStringContainsString('govuk-', $body, 'Expected the branded error page to render');
        self::assertStringNotContainsString('id="viewport-size-monitor"', $body,
            'Viewport monitor must not render on a 404 (it reads/writes the session and fires an XHR)');
    }


}
