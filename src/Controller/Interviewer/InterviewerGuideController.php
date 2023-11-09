<?php

namespace App\Controller\Interviewer;

use App\Attribute\RequiresGoogleCloudStorageStreamWrapper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InterviewerGuideController extends AbstractController
{
    #[Route("/guide.pdf", name: "guide_redirect")]
    public function redirectGuide(): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('interviewer_docs_guide'));
    }

    #[Route("/docs/interviewers-guide", name: "docs_guide")]
    #[RequiresGoogleCloudStorageStreamWrapper]
    public function guide(): BinaryFileResponse
    {
        return new BinaryFileResponse(
            new Stream("gs://digital-diary-documents/NTS_Interviewer-Guide.pdf"),
            public: false,
        );
    }

    #[Route("/docs/hotspot-instructions", name: "docs_hotspot")]
    #[RequiresGoogleCloudStorageStreamWrapper]
    public function hotspot(): Response
    {
        return new BinaryFileResponse(
            new Stream("gs://digital-diary-documents/Smartphone Hotspot Instructions.pdf"),
            public: false,
        );
    }
}