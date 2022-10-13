<?php

namespace App\Controller\Interviewer;

use App\Entity\Household;
use App\Utility\ConfirmAction\Interviewer\SubmitHouseholdConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/household/{household}", name="household_")
 */
class SubmitController extends AbstractController
{
    /**
     * @Route("/submit", name="submit")
     * @Template("interviewer/dashboard/household_submit.html.twig")
     * @IsGranted("SUBMIT_HOUSEHOLD", subject="household")
     */
    public function submit(SubmitHouseholdConfirmAction $confirmAction, Household $household, Request $request)
    {
        return $confirmAction
            ->setSubject($household)
            ->controller($request, $this->generateUrl('interviewer_dashboard_household', ['household' => $household->getId()]));
    }
}