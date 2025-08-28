<?php

namespace App\Controller\Api;

use App\Entity\AreaPeriod;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Interviewer;

class AreaDeallocationController extends AbstractController
{
    public function __invoke(
        #[MapEntity(mapping: ['serialId' => 'serialId'])]
        Interviewer $interviewer,
        #[MapEntity(mapping: ['area' => 'area', 'year' => 'year'])]
        AreaPeriod $areaPeriod,
    ): Interviewer
    {
        $interviewer->removeAreaPeriod($areaPeriod);
        return $interviewer;
    }
}
