<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
    }

    protected function submitLoginForm(string $username, string $password): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Sign in');
        $this->assertEquals('/login', $this->client->getRequest()->getRequestUri());

        $this->client->submitForm('Sign in', [
            'user_login[group][email]' => $username,
            'user_login[group][password]' => $password,
        ]);
    }

    public function assertUrlEquals(string $expectedUrl): void {
        $this->assertEquals($expectedUrl, $this->client->getRequest()->getRequestUri());
    }
}