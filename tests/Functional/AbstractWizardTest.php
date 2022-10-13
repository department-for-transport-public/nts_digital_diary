<?php

namespace App\Tests\Functional;

use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\WizardAction;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class AbstractWizardTest extends AbstractFunctionalTestCase
{
    protected function doWizardTest(array $wizardActions, string $basePath = '')
    {
        $context = $this->createContext($basePath);

        foreach ($wizardActions as $actionIdx => $action) {
            if (!$action instanceof WizardAction) {
                throw new \RuntimeException("Entry $actionIdx in wizard test not an WizardAction");
            }

            $context->set('_actionIndex', $actionIdx);
            $action->perform($context);
        }
    }

    protected function createContext(string $basePath): Context
    {
        $debugLevel = getenv('DEBUG') ?? 0;
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $output = ($debugLevel > 0) ? new ConsoleOutput() : null;
        $context = new Context($this->client, $entityManager, $this, $output, [
            'basePath' => $basePath,
            'debugLevel' => $debugLevel,
        ]);

        if ($debugLevel > 0) {
            $dataSetName = $this->getDataSetName() ?? "Data set start";
            $output->writeln("\n<question>{$dataSetName}</question>\n");
        }
        return $context;
    }

    public function getDataSetName(): ?string
    {
        return preg_match('/^ with data set "(.*)"$/', $this->getDataSetAsString(false), $matches) ?
            $matches[1] : null;
    }
}