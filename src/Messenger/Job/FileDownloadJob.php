<?php

namespace App\Messenger\Job;

class FileDownloadJob extends Job
{
    public function __construct(string $provider, protected string $description, protected string $filename)
    {
        parent::__construct($provider);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}