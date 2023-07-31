<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\TravelDiary\StageState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route(name="stage_wizard_")
 * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
 */
class StageWizardController extends AbstractSessionStateFormWizardController
{
    private ?Journey $journey = null;
    private ?Stage $stage = null;
    private bool $isAdd;

    /**
     * @Route("/journey/{journeyId}/add-stage/{place}", name="place")
     * @Route("/journey/{journeyId}/add-stage", name="start")
     * @IsGranted(DiaryAccessVoter::ACCESS, subject="journey", statusCode=404)
     * @Entity("journey", expr="repository.findByJourneyId(journeyId)")
     * @throws ExceptionInterface
     */
    public function add(Request $request, Journey $journey, ?string $place = null): Response
    {
        $this->journey = $journey;

        return $this->doWorkflow($request, $place, [
            'isAdd' => $this->isAdd = true,
        ]);
    }

    /**
     * @Route("/stage/{stageId}/edit/{place}", name="edit")
     * @IsGranted(DiaryAccessVoter::ACCESS, subject="stage", statusCode=404)
     * @Entity("stage", expr="repository.findByStageId(stageId)")
     * @throws ExceptionInterface
     */
    public function edit(Request $request, Stage $stage, ?string $place = null): Response
    {
        $this->stage = $stage;

        return $this->doWorkflow($request, $place, [
            'isAdd' => $this->isAdd = false,
        ]);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var StageState $state */
        $state = $this->session->get($this->getSessionKey(), new StageState());
        $baseEntity = $this->stage ?: (new Stage())->setJourney($this->journey)->autoAssignNumber();

        return $state->setSubject($this->stage = $this->propertyMerger->merge(
            $baseEntity, $state->getSubject(), Stage::MERGE_PROPERTIES
        ));
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        $url = !$this->isAdd
            ? $this->generateUrl('traveldiary_stage_wizard_edit', ['stageId' => $this->stage->getId(), 'place' => strval($place)])
            : $this->generateUrl('traveldiary_stage_wizard_place', ['journeyId' => $this->journey->getId(), 'place' => strval($place)]);

        return new RedirectResponse($url);
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->stage->getJourney()->getId()]);

        return new RedirectResponse($url . ($this->stage->getId() ? "#stage-{$this->stage->getNumber()}" : ''));
    }
}