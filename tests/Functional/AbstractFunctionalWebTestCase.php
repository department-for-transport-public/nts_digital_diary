<?php

namespace App\Tests\Functional;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AbstractFunctionalWebTestCase extends WebTestCase
{
    use WebTestCaseLoginTrait;

    protected KernelBrowser $client;
    protected ReferenceRepository $fixtureReferenceRepository;

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

    protected function submitLoginForm(string $username, string $password): void
    {
        $this->client->request('GET', '/login');

        $this->client->submitForm('Sign in', [
            'user_login[group][email]' => $username,
            'user_login[group][password]' => $password,
        ]);
    }
}