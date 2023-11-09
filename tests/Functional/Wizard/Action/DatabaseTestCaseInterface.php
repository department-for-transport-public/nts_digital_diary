<?php

namespace App\Tests\Functional\Wizard\Action;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

interface DatabaseTestCaseInterface
{
    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $testCase): void;
}