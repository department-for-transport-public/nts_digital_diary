<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Utility\Admin\DataExport\FeedbackMetadataDataExport;
use App\Utility\Admin\DataExport\SurveyParadataDataExport;
use App\Utility\Admin\DataExport\SatisfactionSurveyDataExport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/data-export', name: 'data_export_')]
class DataExportController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
    ) {}

    #[Route(name: 'index')]
    public function index(): Response
    {
        return $this->render('/admin/data-export/index.html.twig', []);
    }

    #[Route('/feedback-metadata', name: 'feedback_metadata')]
    public function exportFeedbackMetadata(FeedbackMetadataDataExport $dataExport): Response
    {
        return $dataExport->getExportDataResponse();
    }

    #[Route('/satisfaction-survey', name: 'satisfaction_survey')]
    public function exportSatisfactionSurvey(SatisfactionSurveyDataExport $dataExport): Response
    {
        return $dataExport->getExportDataResponse();
    }

    #[Route('/paradata', name: 'paradata')]
    public function exportUsageMetadata(SurveyParadataDataExport $dataExport): Response
    {
        return $dataExport->getExportDataResponse();
    }
}
