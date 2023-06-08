<?php

namespace App\ApiPlatform\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Interviewer;
use App\Repository\InterviewerRepository;

final class InterviewerProvider implements ProviderInterface
{
    private InterviewerRepository $interviewerRepository;

    public function __construct(InterviewerRepository $interviewerRepository)
    {
        $this->interviewerRepository = $interviewerRepository;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Interviewer
    {
        return $this->interviewerRepository->findOneBy(['serialId' => $uriVariables['serialId'] ?? null]);
    }
}