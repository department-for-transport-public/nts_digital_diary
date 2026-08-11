<?php

namespace App\Messenger\Job\Providers\AdminDataExport;

use App\Messenger\Job\FileDownloadJob;
use App\Messenger\Job\Job;
use App\Messenger\Job\Providers\ProviderInterface;
use App\Utility\Admin\DataExport\SurveyParadataDataExport;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Storage\StorageClient;

class SurveyParadataProvider implements ProviderInterface
{
    public function __construct(
        protected SurveyParadataDataExport $service,
        protected string $tempBucketName,
    ) {}

    /**
     * @param FileDownloadJob $job
     * @throws GoogleException
     */
    public function __invoke(Job $job): bool
    {
        $data = $this->service->getExportData();
        $bucket = (new StorageClient())->bucket($this->tempBucketName);
        $object = $bucket->getStreamableUploader($data, ['name' => $job->getFilename()]);
        $object->upload();
        return true;
    }
}