<?php

namespace App\Controller\TravelDiary\Wizards\CombinedReflow;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reflow/stage/{stageId}/edit', name: 'reflow_edit_stage_wizard_')]
class EditStageReflowController extends AbstractCombinedReflowController
{
    #[Route(path: '/{place}', name: 'place')]
    #[IsGranted(DiaryAccessVoter::ACCESS, subject: 'stage', statusCode: 404)]
    public function edit(
        Request $request,
        #[MapEntity(expr: "repository.findByStageId(stageId)")]
        Stage $stage,
        ?string $place = null,
    ): Response {
        $this->referenceStage = $stage;

        return $this->doWorkflow($request, $place, $this->getAdditionalViewData(...));
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var CombinedReflowState $state */
        $state = $this->session->get($this->getSessionKey(), new CombinedReflowState());
        $this->reloadStage($state);

        $state->setWizardType(CombinedReflowState::WIZARD_TYPE__EDIT);
        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        if ($bounceBack = $this->bounceBackRedirectResponse($place)) {
            return $bounceBack;
        }

        $url = $this->generateUrl('traveldiary_reflow_edit_stage_wizard_place', ['stageId' => $this->referenceStage->getId(), 'place' => strval($place)]);
        return new RedirectResponse($url);
    }

    protected function getBounceBackJourneyId(): ?string
    {
        return $this->referenceStage->getJourney()->getId();
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->referenceStage->getJourney()->getId()]);
        return new RedirectResponse($url);
    }

}