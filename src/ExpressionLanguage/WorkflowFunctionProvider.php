<?php

namespace App\ExpressionLanguage;

use App\Attribute\AutoconfigureTag\ExpressionLanguageProvider;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\Workflow\Registry;

#[ExpressionLanguageProvider(ExpressionLanguageProvider::SECURITY)]
#[ExpressionLanguageProvider(ExpressionLanguageProvider::ROUTER)]
#[ExpressionLanguageProvider(ExpressionLanguageProvider::VALIDATOR)]
class WorkflowFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function __construct(protected Registry $workflowRegistry) {}

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'workflow_can',
                fn($transitionName, $workflowObject) => "workflow_can($transitionName, $workflowObject)",
                [$this, 'workflowCan']
            ),
        ];
    }

    public function workflowCan($context, $transitionName, $workflowObject): bool
    {
        return $this->workflowRegistry->get($workflowObject)->can($workflowObject, $transitionName);
    }
}