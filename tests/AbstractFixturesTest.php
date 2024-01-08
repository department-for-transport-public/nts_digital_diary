<?php

namespace App\Tests;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AbstractFixturesTest extends KernelTestCase
{
    protected ReferenceRepository $fixtureReferenceRepository;

    public function bootKernelAndLoadFixtures(array $fixtureClassNames): void
    {
        self::bootKernel();

        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();
        $fixtures = $databaseTool->loadFixtures($fixtureClassNames);
        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();
    }

    protected function getFixtureByReference($reference): object
    {
        return $this->fixtureReferenceRepository->getReference($reference);
    }
}