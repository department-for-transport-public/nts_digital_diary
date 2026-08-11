<?php

namespace App\Controller\TravelDiary\Wizards\CombinedReflow;

use App\Entity\Journey\Journey;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use Exception;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reflow/journey/{journeyId}/edit', name: 'reflow_edit_journey_')]
class EditJourneyController extends AbstractCombinedReflowController
{
    /**
     * @throws Exception
     */
    #[IsGranted(DiaryAccessVoter::ACCESS, subject: 'journey', statusCode: 404)]
    #[Route(path: '/{place}', name: 'place')]
    public function edit(
        Request $request,
        #[MapEntity(expr: "repository.findByJourneyId(journeyId)")]
        Journey $journey,
        string $place
    ): Response {
        $this->referenceJourney = $journey;
        return $this->doWorkflow($request, $place, $this->getAdditionalViewData(...));
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var CombinedReflowState $state */
        $state = $this->session->get($this->getSessionKey(), new CombinedReflowState());
        $this->reloadJourney($state);

        $state->setWizardType(CombinedReflowState::WIZARD_TYPE__EDIT);
        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        if ($bounceBack = $this->bounceBackRedirectResponse($place)) {
            return $bounceBack;
        }

        $params = ['journeyId' => $this->referenceJourney->getId(), 'place' => strval($place)];
        $url = $this->generateUrl('traveldiary_reflow_edit_journey_place', $params);
        return new RedirectResponse($url);
    }

    protected function getBounceBackJourneyId(): ?string
    {
        return $this->referenceJourney->getId();
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->referenceJourney->getId()]);
        return new RedirectResponse($url);
    }
}