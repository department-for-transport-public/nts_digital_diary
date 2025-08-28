<?php

namespace App\Tests\Functional\Interviewer;

use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;

class ProxyExitTest extends AbstractProceduralWizardTest
{
    public const TEST_USERNAME = 'interviewer@example.com';

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([UserFixtures::class]);
    }

    public function testProxyExit(): void
    {
        $this->loginUser(self::TEST_USERNAME);

        $this->client->request('GET', '/interviewer/');
        $this->assertEquals("Interviewer dashboard (Test interviewer)", $this->getHeading());

        $this->clickLinkContaining('View archived areas');
        $this->assertEquals("Archived areas", $this->getHeading());

        $this->clickLinkContaining('View');
        $this->assertEquals("Area: 111984", $this->getHeading());

        $this->clickLinkContaining('View');
        $this->assertEquals("Household: 111984 / 08 / 2", $this->getHeading());

        $this->clickLinkContaining('Impersonate', 0);
        $this->assertEquals("Test Diary Keeper (Adult)'s travel diary", $this->getHeading());

        $this->clickLinkContaining('Exit');
        $this->assertEquals("Household: 111984 / 08 / 2", $this->getHeading());
    }
}
