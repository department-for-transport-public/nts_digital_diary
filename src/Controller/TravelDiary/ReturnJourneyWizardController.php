<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\FormWizard\TravelDiary\ReturnJourneyState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("/journey/{journeyId}/return-journey", name="return_journey_wizard_")
 * @Entity("sourceJourney", expr="repository.findByJourneyId(journeyId)")
 * @IsGranted(DiaryAccessVoter::ACCESS, subject="sourceJourney", statusCode=404)
 * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
 */
class ReturnJourneyWizardController extends AbstractSessionStateFormWizardController
{
    private Journey $sourceJourney;

    private const JOURNEY_CLONE_PROPERTIES = [
        /* locations form */    'startLocation', 'isStartHome', 'endLocation', 'isEndHome',
    ];

    /**
     * @Route("/{place}/{stageNumber}", name="place")
     * @Route("", name="start")
     * @throws ExceptionInterface
     */
    public function index(Request $request, Journey $sourceJourney, ?string $place = null, $stageNumber = 0): Response
    {
        $this->sourceJourney = $sourceJourney;
        if ($place !== null) $place = new Place($place, $stageNumber > 0 ? ['stageNumber' => intval($stageNumber)] : []);

        return $this->doWorkflow($request, $place, ['sourceJourney' => $sourceJourney]);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var RepeatJourneyState $state */
        $state = $this->session->get($this->getSessionKey(), new ReturnJourneyState());
        $state->sourceJourneyId = $this->sourceJourney->getId();

        if (!$state->getSubject()) {
            // copy the source journey, reversing the stages
            /** @var Journey $returnJourney */
            $returnJourney = $this->propertyMerger->clone($this->sourceJourney, self::JOURNEY_CLONE_PROPERTIES);

            // Swap start/end locations
            $returnJourney->setStartLocation($this->sourceJourney->getEndLocation());
            $returnJourney->setIsStartHome($this->sourceJourney->getIsEndHome());
            $returnJourney->setEndLocation($this->sourceJourney->getStartLocation());
            $returnJourney->setIsEndHome($this->sourceJourney->getIsStartHome());

            $number = 1;
            foreach (array_reverse($this->sourceJourney->getStages()->toArray()) as $stage)
            {
                /** @var Stage $newStage */
                $newStage = $this->propertyMerger->clone($stage, Stage::RETURN_JOURNEY_CLONE_PROPERTIES);
                $newStage->setNumber($number++);
                $returnJourney->addStage($newStage);
            }
            $state->setSubject($returnJourney);
        } else {
            $baseEntity = $state->getSubject();

            // Seems odd to be merging it with itself, but it reloads relations from the DB, so that they are managed by EM
            $baseEntity = $this->propertyMerger->merge($baseEntity, $state->getSubject(), Journey::MERGE_PROPERTIES);
            $baseEntity = $this->propertyMerger->mergeCollection($baseEntity, $state->getSubject(), 'stages', Stage::MERGE_PROPERTIES);

            // Set the purpose if it's a new Journey (all return journeys are new), and the end is home
            if ($baseEntity->getDiaryDay() && $baseEntity->isGoingHome()) {
                $baseEntity->setPurpose(Journey::TO_GO_HOME);
            }

            /** @var Journey $baseEntity */
            $state->setSubject($baseEntity);
        }

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        /** @var RepeatJourneyState $state */
        $state = $this->getState();
        $params = array_merge($state->getPlaceRouteParameters($place), [
            'journeyId' => $this->sourceJourney->getId(),
        ]);
        return new RedirectResponse($this->generateUrl('traveldiary_return_journey_wizard_place', $params));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->sourceJourney->getId()]));
    }
}