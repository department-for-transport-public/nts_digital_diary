<?php

namespace App\Tests\Functional\Wizard\Action;

abstract class AbstractAction implements WizardAction
{
    protected ?string $name = null;

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function outputDebugHeader(Context $context): void
    {
        if (!$this->isAtLeastDebugLevel($context, 1)) {
            return;
        }

        $output = $context->getOutput();

        $actionIndex = str_pad((($context->get('_actionIndex') ?? 0) + 1).'.', 4);

        $shortClassName = (new \ReflectionClass($this))->getShortName();
        $name = $this->getName() ?? "<{$shortClassName}>";
        $output->writeln("{$actionIndex}<info>{$name}</info>");
    }

    protected function isAtLeastDebugLevel(Context $context, int $debugLevel): bool
    {
        return $context->getOutput() && intval($context->getConfig('debugLevel')) >= $debugLevel;
    }
}