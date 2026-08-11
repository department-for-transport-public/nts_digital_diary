<?php

namespace App\Controller\TravelDiary\Wizards;

use App\Attribute\Redirect;
use App\Entity\Journey\Journey;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\FormWizard\TravelDiary\ShareJourneyState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use App\Security\Voter\TravelDiary\JourneySharingVoter;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Redirect("!is_granted('EDIT_DIARY')", route: "traveldiary_dashboard")]
#[Route(path: '/journey/{journeyId}/share-journey', name: 'share_journey_wizard_')]
#[IsGranted(DiaryAccessVoter::ACCESS, subject: 'sourceJourney', statusCode: 404)]
class ShareJourneyWizardController extends AbstractSessionStateFormWizardController
{
    protected Journey $sourceJourney;

    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/{place}/{stageNumber}', name: 'place')]
    #[Route(path: '', name: 'start')]
    public function index(
        Request $request,
        #[MapEntity(expr: "repository.findByJourneyId(journeyId)")]
        Journey $sourceJourney,
        ?string $place = null,
        $stageNumber = 0
    ): Response
    {
        $this->sourceJourney = $sourceJourney;

        if (!$this->isGranted(JourneySharingVoter::CAN_SHARE_JOURNEYS)) {
            return $this->redirectToRoute('traveldiary_journey_view', ['journeyId' => $sourceJourney->getId()]);
        }

        if ($place !== null) $place = new Place($place, $stageNumber > 0 ? ['stageNumber' => intval($stageNumber)] : []);

        return $this->doWorkflow($request, $place);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var ShareJourneyState $state */
        $state = $this->session->get($this->getSessionKey(), new ShareJourneyState());

        if ($state->getSubject())
        {
            // Merge for the sharedTo journeys
            foreach($state->getSubject()->getSharedToJourneysBeingAdded() as $sharedJourney) {
                $sharedJourney = $this->propertyMerger->merge($sharedJourney, $sharedJourney, ['diaryDay']);
                $sharedJourney = $this->propertyMerger->mergeCollection($sharedJourney, $sharedJourney, 'stages', ['method', 'vehicle']);

                $this->sourceJourney->addSharedTo($sharedJourney);
            }
        }

        $state->setSubject($this->sourceJourney);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        /** @var RepeatJourneyState $state */
        $state = $this->getState();
        $params = array_merge($state->getPlaceRouteParameters($place), [
            'journeyId' => $this->sourceJourney->getId(),
        ]);
        return new RedirectResponse($this->generateUrl('traveldiary_share_journey_wizard_place', $params));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->sourceJourney->getId()]));
    }
}
