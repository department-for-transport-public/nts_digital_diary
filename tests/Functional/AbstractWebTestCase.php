<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractWebTestCase extends WebTestCase
{
    use WebTestCaseLoginTrait;
    protected KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
    }

    public function assertUrlEquals(string $expectedUrl): void {
        $this->assertEquals($expectedUrl, $this->client->getRequest()->getRequestUri());
    }
}