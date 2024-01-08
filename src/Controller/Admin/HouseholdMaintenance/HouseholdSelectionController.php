<?php

namespace App\Controller\Admin\HouseholdMaintenance;

use App\Controller\AbstractController;
use App\Entity\Household;
use App\Form\Admin\HouseholdMaintenance\HouseholdType;
use App\Repository\HouseholdRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/household-maintenance", name: "household_maintenance_")]
#[Security("is_granted('ROLE_MAINTENANCE')")]
class HouseholdSelectionController extends AbstractController
{
    #[Route(name: "choose_household")]
    public function chooseHousehold(
        HouseholdRepository $householdRepository,
        Request $request,
        TranslatorInterface $translator,
    ): Response
    {
        $form = $this->createForm(HouseholdType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $area = $form->get('area')->getData();
            $addressNumber = $form->get('addressNumber')->getData();
            $householdNumber = $form->get('householdNumber')->getData();

            $household = $householdRepository->findOneBySerial($area, $addressNumber, $householdNumber, false);

            if (!$household) {
                $form->addError(new FormError($translator->trans('household-maintenance.choose-household.no-such-household', [], 'validators')));
            } else {
                return $this->redirectToRoute(
                    'admin_household_maintenance_details',
                    ['id' => $household->getId()]
                );
            }
        }

        return $this->render("admin/household-maintenance/choose-household.html.twig", [
            'form' => $form->createView(),
        ]);
    }

    #[Route("/{id}", name: "details")]
    public function householdDetails(Household $household): Response
    {
        return $this->render("admin/household-maintenance/household-details.html.twig", [
            'household' => $household
        ]);
    }
}