<?php

namespace App\Controller\TravelDiary;

use App\Entity\Vehicle;
use App\Form\TravelDiary\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vehicle/{vehicle}', name: 'vehicle_')]
#[IsGranted('EDIT_VEHICLE', subject: 'vehicle', statusCode: 404)]
class VehicleController extends AbstractController
{
    #[Route(name: 'index')]
    public function index(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return new RedirectResponse($this->generateUrl('traveldiary_dashboard'));
        }

        return $this->render('travel_diary/vehicle/index.html.twig', [
            'form' => $form->createView(),
            'vehicle' => $vehicle,
        ]);
    }
}
