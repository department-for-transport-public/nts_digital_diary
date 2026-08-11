<?php

namespace App\Messenger\Job;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Status
{
    protected \DateTime $timestamp;
    protected mixed     $result = null;

    public function __construct(
        protected State     $state,
        protected Job       $job,
        protected ?string   $errorMessage = null,
        protected array     $context = [],
    ) {
        $this->timestamp = new \DateTime();
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function setState(State $state): self
    {
        $this->state = $state;
        return $this;
    }
    public function getJob(): Job
    {
        return $this->job;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getResult(): mixed
    {
        if ($this->getState() !== State::COMPLETED) {
            throw new NotFoundHttpException('Job not completed');
        }

        return $this->result;
    }

    public function setResult(mixed $result): self
    {
        $this->result = $result;
        return $this;
    }
}
