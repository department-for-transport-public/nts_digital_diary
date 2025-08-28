<?php

namespace App\Controller\Admin\HouseholdMaintenance;

use App\Controller\AbstractController;
use App\Doctrine\ORM\Filter\TrainingAreaPeriodFilter;
use App\Entity\Household;
use App\Form\Admin\HouseholdMaintenance\HouseholdDetailsType;
use App\ListPage\Admin\HouseholdListPage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/household-maintenance", name: "household_maintenance_")]
#[IsGranted("ROLE_MAINTENANCE")]
class HouseholdController extends AbstractController
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        $entityManager->getFilters()->enable(TrainingAreaPeriodFilter::FILTER_NAME);
    }

    #[Route(name: "list")]
    public function list(HouseholdListPage $listPage, Request $request): Response
    {
        $listPage->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render("admin/household-maintenance/list.html.twig", [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm(),
        ]);
    }

    #[Route("/{id}", name: "details")]
    public function householdDetails(Household $household): Response
    {
        return $this->render("admin/household-maintenance/household-details.html.twig", [
            'household' => $household,
        ]);
    }

    #[Route("/{id}/edit", name: "edit")]
    #[IsGranted(attribute: 'EDIT_ONBOARDED_HOUSEHOLD', subject: 'household')]
    public function householdEdit(Request $request, EntityManagerInterface $entityManager, Household $household): Response
    {
        $form = $this->createForm(HouseholdDetailsType::class, $household, [
            'cancel_link_href' => $this->generateUrl('admin_household_maintenance_details', ['id' => $household->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager->flush();
            return new RedirectResponse($this->generateUrl('admin_household_maintenance_details', ['id' => $household->getId()]));
        }

        return $this->render("admin/household-maintenance/edit.html.twig", [
            'household' => $household,
            'form' => $form,
        ]);
    }

}