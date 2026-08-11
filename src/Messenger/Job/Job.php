<?php

namespace App\Messenger\Job;

use App\Messenger\AbstractAsyncMessage;
use Symfony\Component\Uid\Ulid;

class Job extends AbstractAsyncMessage
{
    protected string $id;

    public function __construct(
        protected string $provider,
        protected array $data = [],
    ) {
        $this->id = Ulid::generate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
