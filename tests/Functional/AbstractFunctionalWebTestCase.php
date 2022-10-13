<?php

namespace App\Tests\Functional;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AbstractFunctionalWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    private ReferenceRepository $fixtureReferenceRepository;

    public function initialiseClientAndLoadFixtures(array $fixtures): void
    {
        $this->client = static::createClient();

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $fixtures = $databaseTool->loadFixtures($fixtures);
        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();
    }

    protected function getFixtureByReference($reference): object
    {
        return $this->fixtureReferenceRepository->getReference($reference);
    }
}