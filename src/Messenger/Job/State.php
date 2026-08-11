<?php

namespace App\Messenger\Job;

enum State: string
{
    case NEW = 'new';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
