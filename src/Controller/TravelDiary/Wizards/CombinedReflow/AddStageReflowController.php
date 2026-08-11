<?php

namespace App\Controller\TravelDiary\Wizards\CombinedReflow;

use App\Entity\Journey\Journey;
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

#[Route(path: '/reflow/journey/{journeyId}', name: 'reflow_add_stage_wizard_')]
class AddStageReflowController extends AbstractCombinedReflowController
{
    #[Route(path: '/add-stage', name: 'start')]
    #[Route(path: '/add-stage/{place}', name: 'place')]
    #[IsGranted(DiaryAccessVoter::ACCESS, subject: 'journey', statusCode: 404)]
    public function add(
        Request $request,
        #[MapEntity(expr: "repository.findByJourneyId(journeyId)")]
        Journey $journey,
        ?string $place = null,
    ): Response {
        $this->referenceJourney = $journey;

        if (!$place) {
            $place = CombinedReflowState::STATE_STANDARD__STAGE_METHOD;
        }

        return $this->doWorkflow($request, $place, $this->getAdditionalViewData(...));
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var CombinedReflowState $state */
        $state = $this->session->get($this->getSessionKey(), new CombinedReflowState());
        $this->reloadStage($state);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        if ($bounceBack = $this->bounceBackRedirectResponse($place)) {
            return $bounceBack;
        }

        $url = $this->generateUrl('traveldiary_reflow_add_stage_wizard_place', ['journeyId' => $this->referenceJourney->getId(), 'place' => strval($place)]);
        return new RedirectResponse($url);
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->referenceJourney->getId()]);
        return new RedirectResponse($url);
    }

}