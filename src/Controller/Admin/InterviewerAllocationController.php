<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\AreaPeriod;
use App\Entity\Interviewer;
use App\Form\Admin\AllocateAreaType;
use App\Utility\AreaPeriodHelper;
use App\Utility\ConfirmAction\Admin\InterviewerDeallocateAreaConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/interviewers/{id}", name="interviewers_")
 */
class InterviewerAllocationController extends AbstractController
{
    /**
     * @Route("/deallocate/{area}", name="deallocate")
     */
    public function deallocate(Request $request, InterviewerDeallocateAreaConfirmAction $confirmAction, Interviewer $interviewer, AreaPeriod $area): Response
    {
        $data = $confirmAction
            ->setSubject($interviewer)
            ->setAreaPeriod($area)
            ->controller($request, $this->generateUrl('admin_interviewers_view', ["id" => $interviewer->getId()]));
        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/interviewer/deallocate.html.twig", $data);
    }

    /**
     * @Route("/allocate", name="allocate")
     */
    public function allocate(EntityManagerInterface $entityManager, Request $request, Interviewer $interviewer): Response
    {
        $form = $this->createForm(AllocateAreaType::class, null, [
            'cancel_link_href' => $this->generateUrl('admin_interviewers_view', ['id' => $interviewer->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $areaId = $form->get('area')->getData();
            // create area if not exists
            $areaPeriod = $entityManager
                ->getRepository(AreaPeriod::class)
                ->findOneBy([
                    'area' => $areaId,
                    'year' => AreaPeriodHelper::guessYearFromArea($areaId),
                ]);
            if (!$areaPeriod) {
                $areaPeriod = (new AreaPeriod())
                    ->setArea($areaId)
                    ->populateMonthAndYearFromArea();
                $entityManager->persist($areaPeriod);
            }

            // subscribe to area
            if (!$areaPeriod->getInterviewers()->contains($interviewer)) {
                $areaPeriod->addInterviewer($interviewer);
            }

            $entityManager->flush();

            // redirect to areaperiod
            return $this->redirectToRoute('admin_interviewers_view', ["id" => $interviewer->getId()]);
        }

        return $this->render('admin/interviewer/allocate.html.twig', [
            'form' => $form->createView(),
            'interviewer' => $interviewer,
        ]);
    }
}