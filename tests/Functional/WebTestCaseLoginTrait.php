<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait WebTestCaseLoginTrait
{
    protected KernelBrowser $client;

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
}