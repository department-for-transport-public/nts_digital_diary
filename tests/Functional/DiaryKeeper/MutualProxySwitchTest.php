<?php

declare(strict_types=1);

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\DiaryKeeper;
use App\Tests\DataFixtures\TestSpecific\MutualProxyHouseholdFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 *  Ensure that we don't get a 500 when proxying someone who can also proxy others
 */
final class MutualProxySwitchTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([MutualProxyHouseholdFixtures::class]);
        $this->client->followRedirects();

        $dkA = $this->fixtureReferenceRepository->getReference('diary-keeper:mutual-proxy:a', DiaryKeeper::class);
        $this->loginUser($dkA->getUser()->getUserIdentifier());
    }

    public function testImpersonatingMutualProxyUsers(): void
    {
        $this->client->request(Request::METHOD_GET, '/');
        $dkB = $this->fixtureReferenceRepository->getReference('diary-keeper:mutual-proxy:b', DiaryKeeper::class);

        $this->client->clickLink("Switch to {$dkB->getName()}'s travel diary");
        self::assertSame("{$dkB->getName()}'s travel diary", $this->getHeading());
    }
}
