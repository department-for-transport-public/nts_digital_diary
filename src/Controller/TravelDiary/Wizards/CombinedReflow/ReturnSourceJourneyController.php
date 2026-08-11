<?php

namespace App\Controller\TravelDiary\Wizards\CombinedReflow;

use App\Entity\Journey\Journey;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use App\Utility\TravelDiary\DuplicateJourneyHelper;
use Exception;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reflow/journey/{journeyId}/return-journey', name: 'reflow_return_journey_wizard_')]
class ReturnSourceJourneyController extends AbstractCombinedReflowController
{
    Private Journey $sourceJourney;

    /**
     * @throws Exception
     */
    #[Route(path: '/{place}/{stageNumber}', name: 'place')]
    #[Route(path: '', name: 'start')]
    #[IsGranted(DiaryAccessVoter::ACCESS, subject: 'journey', statusCode: 404)]
    public function add(
        Request $request,
        DuplicateJourneyHelper $duplicateJourneyHelper,
        #[MapEntity(expr: "repository.findByJourneyId(journeyId)")]
        Journey $journey,
        ?string $place = null,
        $stageNumber = 0,
    ): Response {
        $this->sourceJourney = $journey;
        if ($place !== null) $place = new Place($place, $stageNumber > 0 ? ['stageNumber' => intval($stageNumber)] : []);
        else {
            $place = new Place(CombinedReflowState::STATE_RETURN__TARGET_DAY, ['stageNumber' => 0]);

            $this->referenceJourney = $duplicateJourneyHelper->cloneSourceJourney($journey, CombinedReflowState::WIZARD_TYPE__RETURN);

            $state = $this->getState();
            $state->setWizardType(CombinedReflowState::WIZARD_TYPE__RETURN);
            $state->sourceJourneyId = $journey->getId();
            $this->setState($state);
        }

        return $this->doWorkflow($request, $place, $this->getAdditionalViewData(...));

    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        if ($bounceBack = $this->bounceBackRedirectResponse($place)) {
            return $bounceBack;
        }

        $state = $this->getState();
        $params = array_merge(
            ['journeyId' => $this->sourceJourney->getId()],
            $state->getPlaceRouteParameters($place),
        );
        $url = $this->generateUrl('traveldiary_reflow_return_journey_wizard_place', $params);

        return new RedirectResponse($url);
    }

    protected function getBounceBackJourneyId(): ?string
    {
        return $this->sourceJourney->getId();
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->sourceJourney->getId()]);
        return new RedirectResponse($url);
    }

    public function getSessionKey(): string
    {
        return "wizard." . AddJourneyController::class;
    }
}