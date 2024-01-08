<?php

namespace App\Controller\Interviewer\Training;

use App\Repository\InterviewerTrainingRecordRepository;
use App\Utility\Security\UrlSigner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/training', name: 'training_')]
class DashboardController extends AbstractController
{
    #[
        Route(name: 'dashboard'),
        Template('interviewer/training/dashboard.html.twig')
    ]
    public function index(InterviewerTrainingRecordRepository $trainingRecordRepository, UserInterface $user): array
    {
        $interviewer = $this->getInterviewer($user);
        return [
            'interviewer' => $interviewer,
            'trainingRecords' => $trainingRecordRepository->findLatestForInterviewer($interviewer),
        ];
    }
}