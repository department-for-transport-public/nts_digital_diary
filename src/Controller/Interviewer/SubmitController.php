<?php

namespace App\Controller\Interviewer;

use App\Entity\Household;
use App\Security\Voter\Interviewer\HouseholdVoter;
use App\Utility\ConfirmAction\Interviewer\SubmitHouseholdConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/household/{household}", name: "household_")]
class SubmitController extends AbstractController
{
    #[Route("/submit", name: "submit")]
    #[Template("interviewer/dashboard/household_submit.html.twig")]
    #[IsGranted(HouseholdVoter::SUBMIT, subject: "household")]
    public function submit(SubmitHouseholdConfirmAction $confirmAction, Household $household, Request $request): Response | array
    {
        return $confirmAction
            ->setSubject($household)
            ->controller($request, $this->generateUrl('interviewer_dashboard_household', ['household' => $household->getId()]));
    }
}