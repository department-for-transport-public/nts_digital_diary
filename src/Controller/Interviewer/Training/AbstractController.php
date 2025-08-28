<?php

namespace App\Controller\Interviewer\Training;

use App\Entity\Interviewer;
use App\Utility\InterviewerTraining\InterviewerTrainingHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

abstract class AbstractController extends \App\Controller\Interviewer\AbstractController
{
    public function __construct(
        EntityManagerInterface              $entityManager,
        protected InterviewerTrainingHelper $trainingHelper,
        Security                            $security,
    )
    {
        parent::__construct($entityManager, $security);
        $this->security = $security;
    }

    protected function getInterviewer(): Interviewer
    {
        $interviewer = parent::getInterviewer();
        $this->trainingHelper->checkOrCreateTrainingData($interviewer);
        return $interviewer;
    }
}
