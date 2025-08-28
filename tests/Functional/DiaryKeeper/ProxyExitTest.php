<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;

class ProxyExitTest extends AbstractProceduralWizardTest
{
    public const TEST_USERNAME = 'diary-keeper-adult@example.com';

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([UserFixtures::class]);
    }

    public function testProxyExit(): void
    {
        $this->loginUser(self::TEST_USERNAME);

        $this->client->request('GET', '/travel-diary/');
        $this->assertEquals("My travel diary", $this->getHeading());

        $this->clickLinkContaining('Switch to Test Diary Keeper');
        $this->assertEquals("Test Diary Keeper (Proxied)'s travel diary", $this->getHeading());

        $this->clickLinkContaining('Go back to my own diary');
        $this->assertEquals("My travel diary", $this->getHeading());
    }
}
