<?php


namespace App\Command;


use App\FormWizard\LocatorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'nts:form-wizard:list', description: 'List the form wizards')]
class ListFormWizardNamesCommand extends Command
{
    public function __construct(protected readonly LocatorService $formWizardLocatorService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->formWizardLocatorService->getWorkflowConfigurations() as $stateMachine) {
            $output->writeln($stateMachine->getName());
        }

        return Command::SUCCESS;
    }
}