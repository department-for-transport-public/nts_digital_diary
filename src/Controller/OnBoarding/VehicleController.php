<?php

namespace App\Controller\OnBoarding;

use App\Entity\Household;
use App\Entity\OtpUser;
use App\Entity\Vehicle;
use App\Form\OnBoarding\VehicleType;
use App\Utility\ConfirmAction\OnBoarding\DeleteVehicleConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="vehicle_")
 * @IsGranted("ONBOARDING_EDIT")
 */
class VehicleController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/vehicle/add", name="add")
     */
    public function add(Request $request): Response
    {
        $vehicle = (new Vehicle());
        $this->getHousehold()->addVehicle($vehicle);

        return $this->addOrEdit($request, $vehicle, 'on_boarding/vehicle/add.html.twig');
    }

    /**
     * @Route("/vehicle/{id}/edit", name="edit")
     */
    public function edit(Request $request, Vehicle $vehicle): Response
    {
        $this->checkIsCorrectHousehold($vehicle->getHousehold());

        return $this->addOrEdit($request, $vehicle, 'on_boarding/vehicle/edit.html.twig');
    }

    /**
     * @Route("/vehicle/{id}/delete", name="delete")
     */
    public function delete(Request $request, DeleteVehicleConfirmAction $confirmAction, Vehicle $vehicle): Response
    {
        $this->checkIsCorrectHousehold($vehicle->getHousehold());

        $data = $confirmAction
            ->setSubject($vehicle)
            ->controller($request,$this->generateUrl('onboarding_dashboard')."#vehicles");

        return $data instanceof Response ? $data : $this->render("on_boarding/vehicle/delete.html.twig", $data);
    }

    protected function addOrEdit(Request $request, Vehicle $vehicle, string $template)
    {
        $returnUrl = $this->generateUrl('onboarding_dashboard');

        $form = $this->createForm(VehicleType::class, $vehicle, [
            'cancel_link_href' => $returnUrl,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($vehicle);
            $this->entityManager->flush();
            return new RedirectResponse($returnUrl);
        }

        return $this->render($template, [
            'form' => $form->createView(),
        ]);
    }

    protected function getHousehold(): Household
    {
        $user = $this->getUser();

        if (!$user instanceof OtpUser || !$user->getHousehold()) {
            throw new NotFoundHttpException();
        }

        return $user->getHousehold();
    }
}