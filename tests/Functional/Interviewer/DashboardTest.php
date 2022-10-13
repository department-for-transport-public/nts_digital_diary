<?php

namespace App\Tests\Functional\Interviewer;

use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;

class DashboardTest extends AbstractFunctionalTestCase
{
    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            UserFixtures::class
        ]);

        $this->loginUser('interviewer@example.com');
    }

    public function testDashboard(): void
    {
        $this->client->request('GET', '/interviewer/');

        $tableData = $this->getTableData();
        $this->assertDataSize($tableData, 1);

        $this->assertEquals('2021', $this->getTableCaption());
        $this->assertCount(1, $tableData, 'Expected table to have one row');
        $this->assertEquals(111984, $tableData[0][0], 'Expected area number to be 111984');
        $this->assertEquals(1, $tableData[0][1], 'Expected number of active households to be 1');
    }

    public function testArea(): void
    {
        $this->client->request('GET', '/interviewer/');
        $this->clickLinkContaining('View');
        $tableData = $this->getTableData();

        $expectedSerials = ['111984 / 08 / 2'];
        $expectedDiaryKeepers = ['4'];
        $expectedStartDate = ['2021/11/22'];

        $this->assertEquals('Area: 111984', $this->getHeading());
        $this->assertCount(count($expectedSerials), $tableData, 'Expected table to have two rows');

        for($idx = 0; $idx < count($expectedSerials); $idx++) {
            $this->assertEquals($expectedSerials[$idx], $tableData[$idx][0], "Expected serial on row #{$idx} to be ${expectedSerials[$idx]}");
            $this->assertEquals($expectedDiaryKeepers[$idx], $tableData[$idx][1], "Expected diary keeper count on row #{$idx} to be ${expectedDiaryKeepers[$idx]}");
            $this->assertEquals(
                \DateTime::createFromFormat('Y/m/d', $expectedStartDate[$idx]),
                \DateTime::createFromFormat('d/m/Y', $tableData[$idx][2]),
                "Expected start date on row #{$idx} to be ${expectedStartDate[$idx]}"
            );
        }
    }

    public function testHouseholdWithDiaryKeepers(): void
    {
        $this->client->request('GET', '/interviewer/');
        $this->clickLinkContaining('View');
        $this->clickLinkContaining('View', 0);

        $this->assertEquals('Household: 111984 / 08 / 2', $this->getHeading());

        $this->expectHouseholdDiaryWeekStartDate('2021/11/22');

        $tableData = $this->getTableData();

        $this->assertDataSize($tableData, 4);

        $this->assertEquals('Test Diary Keeper (Adult)', $tableData[0][1]);
        $this->assertEquals('Test Diary Keeper (Child)', $tableData[1][1]);
    }

    public function testDashboardBreadcrumbs(): void
    {
        $this->doBreadcrumbsTestStartingAt('/interviewer/', 3);
    }

    public function testDashboardBackLinks(): void
    {
        $this->doBackLinksTestStartingAt('/interviewer/', 3);
    }

    protected function expectHouseholdDiaryWeekStartDate(string $expectedStartDate): void
    {
        $summaryListData = $this->getSummaryListData();
        $this->assertDataSize($summaryListData, 2, 2);

        $this->assertEquals(
            \DateTime::createFromFormat('Y/m/d', $expectedStartDate),
            \DateTime::createFromFormat('d/m/Y', $summaryListData[0][1]),
            "Expected start date to be ${expectedStartDate}"
        );
    }
}
