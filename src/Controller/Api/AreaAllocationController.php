<?php

namespace App\Controller\Api;

use App\Entity\AreaPeriod;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Interviewer;

class AreaAllocationController extends AbstractController
{
    public function __invoke(Interviewer $interviewer, AreaPeriod $areaPeriod)
    {
        $interviewer->addAreaPeriod($areaPeriod);
        return $interviewer;
    }
}