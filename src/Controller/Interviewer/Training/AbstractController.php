<?php

namespace App\Controller\Interviewer\Training;

use App\Entity\Interviewer;
use App\Utility\InterviewerTraining\InterviewerTrainingHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractController extends \App\Controller\Interviewer\AbstractController
{
    protected InterviewerTrainingHelper $trainingHelper;

    public function __construct(EntityManagerInterface $entityManager, InterviewerTrainingHelper $trainingHelper, Security $security)
    {
        parent::__construct($entityManager, $security);
        $this->trainingHelper = $trainingHelper;
        $this->security = $security;
    }

    protected function getInterviewer(): Interviewer
    {
        $interviewer = parent::getInterviewer($this->security->getUser());
        $this->trainingHelper->checkOrCreateTrainingData($interviewer);
        return $interviewer;
    }
}