<?php

namespace App\Controller\TravelDiary\Wizards;

use App\Attribute\Redirect;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\FormWizard\TravelDiary\ReturnJourneyState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Metadata\PlaceMetadata;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Redirect("!is_granted('EDIT_DIARY')", route: "traveldiary_dashboard")]
#[Route(path: '/journey/{journeyId}/return-journey', name: 'return_journey_wizard_')]
#[IsGranted(DiaryAccessVoter::ACCESS, subject: 'sourceJourney', statusCode: 404)]
class ReturnJourneyWizardController extends AbstractSessionStateFormWizardController
{
    private Journey $sourceJourney;

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
        if ($place !== null) $place = new Place($place, $stageNumber > 0 ? ['stageNumber' => intval($stageNumber)] : []);

        $getAdditionalViewData = function(ReturnJourneyState $state, PlaceMetadata $placeMetadata): array {
            $data = ['sourceJourney' => $this->sourceJourney];

            if ($placeMetadata->hasContextEntry('set_translation_key_from_state')) {
                $data['title_translation_key'] = $state->getStageDetailsTitle();
            }

            return $data;
        };

        return $this->doWorkflow($request, $place, $getAdditionalViewData);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var RepeatJourneyState $state */
        $state = $this->session->get($this->getSessionKey(), new ReturnJourneyState());
        $state->sourceJourneyId = $this->sourceJourney->getId();

        if (!$state->getSubject()) {
            // copy the source journey, reversing the stages
            /** @var Journey $returnJourney */
            $returnJourney = $this->propertyMerger->clone($this->sourceJourney, Journey::RETURN_JOURNEY_CLONE_PROPERTIES);

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
