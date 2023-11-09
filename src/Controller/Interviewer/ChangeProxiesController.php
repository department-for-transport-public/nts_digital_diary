<?php

namespace App\Controller\Interviewer;

use App\Entity\DiaryKeeper;
use App\Form\Auth\ChangeProxiesType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChangeProxiesController extends AbstractController
{
    /**
     * @Route("/change-proxies/{diaryKeeper}", name="change_proxies")
     * @IsGranted("CHANGE_PROXIES", subject="diaryKeeper", statusCode=403)
     */
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

        return $this->render('interviewer/change_proxies.html.twig', [
            'form' => $form->createView(),
            'user' => $user,

            'areaPeriod' => $household->getAreaPeriod(),
            'diaryKeeper' => $diaryKeeper,
            'household' => $household,
        ]);
    }
}