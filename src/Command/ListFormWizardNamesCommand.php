<?php


namespace App\Command;


use App\FormWizard\LocatorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListFormWizardNamesCommand extends Command
{
    protected static $defaultName = 'nts:form-wizard:list';
    protected static $defaultDescription = 'List the form wizards';
    private LocatorService $formWizardLocatorService;

    public function __construct(LocatorService $formWizardLocatorService)
    {
        parent::__construct();
        $this->formWizardLocatorService = $formWizardLocatorService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->formWizardLocatorService->getWorkflowConfigurations() as $stateMachine) {
            $output->writeln($stateMachine->getName());
        }

        return 0;
    }
}