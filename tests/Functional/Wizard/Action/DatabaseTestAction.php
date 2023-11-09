<?php

namespace App\Tests\Functional\Wizard\Action;


use Doctrine\ORM\EntityManagerInterface;

class DatabaseTestAction extends AbstractAction
{
    /**
     * @param DatabaseTestCaseInterface[] | array $testCases
     */
    public function __construct(protected array $testCases = []) {}

    public function perform(Context $context): void
    {
        foreach ($this->testCases as $testCase) {
            $context->getEntityManager()->clear();
            $testCase->checkDatabaseAsExpected($context->getEntityManager(), $context->getTestCase());
        }
    }
}