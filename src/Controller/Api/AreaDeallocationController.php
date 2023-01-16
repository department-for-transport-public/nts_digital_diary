<?php

namespace App\Controller\Api;

use App\Entity\AreaPeriod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Interviewer;

class AreaDeallocationController extends AbstractController
{
    public function __invoke(Interviewer $interviewer, AreaPeriod $areaPeriod): Interviewer
    {
        $interviewer->removeAreaPeriod($areaPeriod);
        return $interviewer;
    }
}