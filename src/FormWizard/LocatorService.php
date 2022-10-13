<?php


namespace App\FormWizard;


class LocatorService
{
    private iterable $workflowConfigurations;

    public function __construct(iterable $workflowConfigurations)
    {
        $this->workflowConfigurations = $workflowConfigurations;
    }

    /**
     * @return iterable
     */
    public function getWorkflowConfigurations(): iterable
    {
        return $this->workflowConfigurations;
    }
}