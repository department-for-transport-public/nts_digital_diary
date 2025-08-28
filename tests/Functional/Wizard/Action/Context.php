<?php

namespace App\Tests\Functional\Wizard\Action;

use App\Tests\Functional\AbstractWizardTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Panther\Client;

/**
 * Context is a simple mechanism that can be used to:
 * a) Fetch useful test-specific instantiations (client, entityManager, testCase)
 * b) Share data between WizardActions which use callbacks
 */
class Context
{
    protected array $context;

    public function __construct(protected Client $client, protected EntityManagerInterface $entityManager, protected AbstractWizardTest $testCase, protected ?OutputInterface $output, protected array $config)
    {
        $this->context = [];
    }

    public function get(?string $key): string
    {
        return $this->context[$key];
    }

    public function all(): array
    {
        return $this->context;
    }

    public function set(string $key, string $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getTestCase(): AbstractWizardTest
    {
        return $this->testCase;
    }

    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    public function getConfig(string $key)
    {
        return $this->config[$key];
    }
}