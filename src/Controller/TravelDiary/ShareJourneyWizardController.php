<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\Journey\Journey;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\PropertyMerger;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\FormWizard\TravelDiary\ShareJourneyState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use App\Security\Voter\TravelDiary\JourneySharingVoter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("/journey/{journeyId}/share-journey", name="share_journey_wizard_")
 * @Entity("sourceJourney", expr="repository.findByJourneyId(journeyId)")
 * @IsGranted(DiaryAccessVoter::ACCESS, subject="sourceJourney", statusCode=404)
 * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
 */
class ShareJourneyWizardController extends AbstractSessionStateFormWizardController
{
    protected Journey $sourceJourney;

    /**
     * @Route("/{place}/{stageNumber}", name="place")
     * @Route("", name="start")
     * @throws ExceptionInterface
     */
    public function index(Request $request, Journey $sourceJourney, ?string $place = null, $stageNumber = 0): Response
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