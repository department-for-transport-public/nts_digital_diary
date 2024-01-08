<?php

namespace App\Controller\Admin\HouseholdMaintenance;

use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\OnBoarding\DiaryKeeperState;
use App\FormWizard\Place;
use App\FormWizard\PropertyMerger;
use App\Repository\DiaryKeeperRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Route("/household-maintenance", name: "household_maintenance_add_diary_keeper_")]
class AddDiaryKeeperWizard extends AbstractSessionStateFormWizardController
{
    protected ?Household $household;

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
    #[Route("/{id}/add-diary-keeper/{place}", name: "place")]
    #[Security("is_granted('HOUSEHOLD_MAINTENANCE_ADD_DIARY_KEEPER', household)")]
    public function index(Household $household, Request $request, ?string $place = null): Response
    {
        $this->household = $household;
        return $this->doWorkflow($request, $place);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var DiaryKeeperState $state */
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