<?php

namespace App\Messenger\Job\Providers\AdminDataExport;

use App\Messenger\Job\Job;
use App\Messenger\Job\Providers\ProviderInterface;
use App\Utility\Admin\DataExport\SatisfactionSurveyDataExport;

class SatisfactionSurveyDataProvider implements ProviderInterface
{
    public function __construct(
        protected SatisfactionSurveyDataExport $service,
    ) {}

    public function __invoke(Job $job): array
    {
        return [
            'data' => $this->service->getExportData(),
        ];
    }
}