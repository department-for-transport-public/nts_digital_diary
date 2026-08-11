<?php

namespace App\Controller\Interviewer\HouseholdManagement;

use App\Controller\Interviewer\AbstractController;
use App\Entity\DiaryKeeper;
use App\Form\Auth\ChangeProxiesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ChangeProxiesController extends AbstractController
{
    #[Route(path: '/change-proxies/{diaryKeeper}', name: 'change_proxies')]
    #[IsGranted('CHANGE_PROXIES', subject: 'diaryKeeper', statusCode: 403)]
    public function changeProxies(Request $request, DiaryKeeper $diaryKeeper, EntityManagerInterface $entityManager): Response
    {
        $user = $diaryKeeper->getUser();
        $household = $diaryKeeper->getHousehold();
        $successUrl = $this->generateUrl('interviewer_dashboard_diary_keeper', [
            'diaryKeeper' => $diaryKeeper->getId(),
        ]);

        $form = $this->createForm(ChangeProxiesType::class, $diaryKeeper, [
            'cancel_link_href' => $successUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->flush();

                $this->addSuccessBanner('diary-keeper.change-proxies', 'interviewer', ['name' => $diaryKeeper->getName()]);
                return new RedirectResponse($successUrl);
            }
        }

        return $this->render('interviewer/dashboard/household_management/change_proxies.html.twig', [
            'form' => $form,
            'user' => $user,

            'areaPeriod' => $household->getAreaPeriod(),
            'diaryKeeper' => $diaryKeeper,
            'household' => $household,
        ]);
    }
}