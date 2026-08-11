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

        $this->getElementByTextContains('span', 'Diary keeper details', 0)->click();

        $data = $this->getSummaryListData('//*[@id="main-content"]/details/div/dl/div');

        $this->assertDataHasKeyValuePair($data, 'Name', 'Test Diary Keeper (Adult)');
        $this->assertDataHasKeyValuePair($data, 'Serial number', '111984 / 08 / 2 / 1');
        $this->assertDataHasKeyValuePair($data, 'Diary status', 'Not yet started');
        $this->assertDataHasKeyValuePair($data, 'Adult / child', 'Adult (16+)');

        // 4 rows as per above, and 7 rows for days
        $this->assertDataSize($data, 4);
    }

    public function testDashboardBreadcrumbs(): void
    {
        $this->doBreadcrumbsTestStartingAt('/travel-diary/', 2);
    }

    public function testDashboardBackLinks(): void
    {
        $this->doBackLinksTestStartingAt('/travel-diary/', 2);
    }

    public function testJourneyCounts(): void
    {
        $this->client->request('GET', '/travel-diary/');

        // example day
        $this->getElementByTextContains('span', 'Examples and practice')->click();
        $this->clickLinkContaining('View example day', 0);
        $data = $this->getSummaryListData();
        $this->assertDataSize($data, 2);
        $this->clickLinkContaining('Back to diary overview');

        // Check that each day's page lists "No journeys"
        // (not including practice day)
        for($idx=0; $idx<=6; $idx++) {
            $this->clickLinkContaining('View', $idx);

            $data = $this->getSummaryListData();
            $this->assertDataHasKeyValuePair($data, 'No journeys', '', "Day $idx");

            $this->clickLinkContaining('Back to diary overview');
        }
    }
}
