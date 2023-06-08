<?php

namespace App\Tests\Functional;

use App\Tests\Functional\Wizard\Action\AbstractAction;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;

abstract class AbstractProceduralWizardTest extends AbstractWizardTest
{
    protected Context $context;

    protected function createContext(string $basePath): Context
    {
        return parent::createContext($basePath)
            ->set('_actionIndex', 0);
    }

    protected function pathTestAction(string $expectedPath, array $options = []): void
    {
        $this->perform(new PathTestAction($expectedPath, $options));
    }

    protected function callbackTestAction(callable $callback): void
    {
        $this->perform(new CallbackAction($callback));
    }

    protected function formTestAction(string $expectedPath, string $submitButtonId = null, array $formTestCases = [], array $options = [])
    {
        $this->perform(new FormTestAction($expectedPath, $submitButtonId, $formTestCases, $options));
    }

    private function perform(AbstractAction $action): void
    {
        $this->context->set('_actionIndex', intval($this->context->get('_actionIndex')) + 1);
        $action->perform($this->context);
    }
}