<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;

class EmptyDashboardTest extends AbstractFunctionalTestCase
{
    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            UserFixtures::class
        ]);

        $this->loginUser('diary-keeper-adult@example.com');
    }

    public function testDashboard(): void
    {
        $this->client->request('GET', '/travel-diary/');

        $data = $this->getSummaryListData();

        $this->assertDataHasKeyValuePair($data, 'Name', 'Test Diary Keeper (Adult)');
        $this->assertDataHasKeyValuePair($data, 'Serial number', '111984 / 08 / 2 / 1');
        $this->assertDataHasKeyValuePair($data, 'Diary status', 'New');

        // 3 rows as per above, and 7 rows for days
        $this->assertDataSize($data, 10);
    }

    public function testDashboardBreadcrumbs(): void
    {
        $this->doBreadcrumbsTestStartingAt('/travel-diary/', 2);
    }

    public function testDashboardBackLinks(): void
    {
        $this->doBackLinksTestStartingAt('/travel-diary/', 2);
    }

    public function testNoJourneys(): void
    {
        $this->client->request('GET', '/travel-diary/');

        // Check that each day's page lists "No journeys"
        for($idx=0; $idx<7; $idx++) {
            $this->clickLinkContaining('View', $idx);

            $data = $this->getSummaryListData();
            $this->assertDataHasKeyValuePair($data, 'No journeys', '');

            $this->clickLinkContaining('Back to diary overview');
        }
    }
}
