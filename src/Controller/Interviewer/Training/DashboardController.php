<?php

namespace App\Controller\Interviewer\Training;

use App\Repository\InterviewerTrainingRecordRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/training', name: 'training_')]
class DashboardController extends AbstractController
{
    #[Route(name: 'dashboard')]
    #[Template('interviewer/training/dashboard.html.twig')]
    public function index(InterviewerTrainingRecordRepository $trainingRecordRepository): array
    {
        $interviewer = $this->getInterviewer();
        return [
            'interviewer' => $interviewer,
            'trainingRecords' => $trainingRecordRepository->findLatestForInterviewer($interviewer),
        ];
    }
}