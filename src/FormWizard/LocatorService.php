<?php


namespace App\FormWizard;


class LocatorService
{
    public function __construct(protected readonly iterable $workflowConfigurations)
    {}

    public function getWorkflowConfigurations(): iterable
    {
        return $this->workflowConfigurations;
    }
}