<?php

namespace App\Messenger\Job\Providers;

use App\Messenger\Job\Job;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ghost.job.provider')]
interface ProviderInterface
{
    public function __invoke(Job $job): mixed;
}