<?php

namespace App\Controller\Admin\HouseholdMaintenance;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\FormWizard\OnBoarding\DiaryKeeperState;
use App\Repository\DiaryKeeperRepository;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Route("/household-maintenance/{id}/add-diary-keeper", name: "household_maintenance_add_diary_keeper_")]
#[IsGranted(attribute: 'HOUSEHOLD_MAINTENANCE_ADD_DIARY_KEEPER', subject: 'household')]
class AddDiaryKeeperWizard extends AbstractSessionStateFormWizardController
{
    protected ?Household $household = null;

    public function __construct(
        protected DiaryKeeperRepository $diaryKeeperRepository,
        FormWizardManager $formWizardManager,
        RequestStack $requestStack,
        PropertyMerger $propertyMerger
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route("/{place}", name: "place")]
    public function index(Household $household, Request $request, ?string $place = null): Response
    {
        $this->household = $household;
        return $this->doWorkflow($request, $place);
    }

    protected function getState(): FormWizardStateInterface
    {
        $default = (new DiaryKeeperState())->setAddAnotherDisabled(true);
        $state = $this->session->get($this->getSessionKey(), $default);

        $diaryKeeper = $this->propertyMerger->merge(new DiaryKeeper(), $state->getSubject(), [
            /* details form */  'name', 'number', 'isAdult',
            /* identity form */ 'user', '?user.username', 'proxies',
        ]);

        $this->household->addDiaryKeeper($diaryKeeper);
        return $state->setSubject($diaryKeeper);
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('admin_household_maintenance_add_diary_keeper_place', [
            'id' => $this->household->getId(),
            'place' => strval($place)
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('admin_household_maintenance_details', [
            'id' => $this->household->getId(),
        ]));
    }
}