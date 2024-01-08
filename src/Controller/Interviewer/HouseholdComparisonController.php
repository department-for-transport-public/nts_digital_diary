<?php

namespace App\Controller\Interviewer;

use App\Entity\Household;
use App\Utility\Metrics\Events\HouseholdComparisonEvent;
use App\Utility\Metrics\MetricsHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/household/{household}/compare", name: "household_compare_")]
class HouseholdComparisonController extends AbstractController
{
    #[Route("/day-{day}", name: "day")]
    #[Template("interviewer/dashboard/household_compare.html.twig")]
    public function day(MetricsHelper $metrics, Household $household, int $day = 1): array
    {
        $metrics->log(new HouseholdComparisonEvent(
            $household->getSerialNumber(...MetricsHelper::GET_SERIAL_METHOD_ARGS),
            $day
        ));
        return [
            'currentDay' => $day,
            'household' => $household,
        ];
    }
}