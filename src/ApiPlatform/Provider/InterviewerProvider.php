<?php

namespace App\ApiPlatform\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Interviewer;
use App\Repository\InterviewerRepository;

final readonly class InterviewerProvider implements ProviderInterface
{
    public function __construct(protected InterviewerRepository $interviewerRepository)
    {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Interviewer
    {
        return $this->interviewerRepository->findOneBy(['serialId' => $uriVariables['serialId'] ?? null]);
    }
}