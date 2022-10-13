<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\PropertyMerger;
use App\FormWizard\TravelDiary\StageState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route(name="stage_wizard_")
 * @Redirect("is_granted('DIARY_KEEPER_WITH_APPROVED_DIARY')", route="traveldiary_dashboard")
 */
class StageWizardController extends AbstractSessionStateFormWizardController
{
    private ?string $stageId;
    private ?string $journeyId;
    private Stage $stage;

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, FormWizardManager $formWizardManager, RequestStack $requestStack, PropertyMerger $propertyMerger)
    {
        $this->entityManager = $entityManager;
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @Route("/journey/{journeyId}/add-stage/{place}", name="place")
     * @Route("/journey/{journeyId}/add-stage", name="start")
     * @Route("/stage/{stageId}/edit/{place}", name="edit")
     * @throws ExceptionInterface
     */
    public function index(Request $request, ?string $journeyId = null, ?string $place = null, ?string $stageId = null): Response
    {
        $this->journeyId = $journeyId;
        $this->stageId = $stageId;

        return $this->doWorkflow($request, $place, [
            'isAdd' => ($stageId === null),
        ]);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var StageState $state */
        $state = $this->session->get($this->getSessionKey(), new StageState());
        $baseEntity = $this->stageId
            ? $this->entityManager->getRepository(Stage::class)->findByStageId($this->stageId)
            : (new Stage())->setJourney($this->entityManager->find(Journey::class, $this->journeyId))->autoAssignNumber();

        return $state->setSubject($this->stage = $this->propertyMerger->merge(
            $baseEntity, $state->getSubject(), Stage::MERGE_PROPERTIES
        ));
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        $url = $this->stageId ?
            $this->generateUrl('traveldiary_stage_wizard_edit', ['stageId' => $this->stageId, 'place' => strval($place)]) :
            $this->generateUrl('traveldiary_stage_wizard_place', ['journeyId' => $this->journeyId, 'place' => strval($place)]);

        return new RedirectResponse($url);
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->stage->getJourney()->getId()]);

        return new RedirectResponse($url . ($this->stageId ? "#stage-{$this->stage->getNumber()}" : ''));
    }
}