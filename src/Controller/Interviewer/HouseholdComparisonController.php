<?php

namespace App\Controller\Interviewer;

use App\Entity\Household;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/household/{household}/compare", name: "household_compare_")]
class HouseholdComparisonController extends AbstractController
{
    #[Route("/day-{day}", name: "day")]
    #[Template("interviewer/dashboard/household_compare.html.twig")]
    public function day(Household $household, int $day = 1): array
    {
        return [
            'currentDay' => $day,
            'household' => $household,
        ];
    }
}