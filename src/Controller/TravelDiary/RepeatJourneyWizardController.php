<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\Utility\TravelDiary\RepeatJourneyHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("/repeat-journey", name="repeat_journey_wizard_")
 * @Redirect("is_granted('DIARY_KEEPER_WITH_APPROVED_DIARY')", route="traveldiary_dashboard")
 */
class RepeatJourneyWizardController extends AbstractSessionStateFormWizardController
{
    private ?bool $fromPracticeDay = null;

    /**
     * @Route("/day-{dayNumber}", name="start")
     * @throws ExceptionInterface
     */
    public function start(Request $request, int $dayNumber): Response
    {
        $this->setState((new RepeatJourneyState())->setIsPracticeDay($dayNumber === 0));

        return $this->doWorkflow($request, null);
    }

    /**
     * @Route("/{place}/{stageNumber}", name="place")
     * @throws ExceptionInterface
     */
    public function index(Request $request, ?string $place = null, $stageNumber = 0): Response
    {
        if ($place !== null) $place = new Place($place, $stageNumber > 0 ? ['stageNumber' => intval($stageNumber)] : []);

        return $this->doWorkflow($request, $place);
    }

    /**
     * @Route("/source-journey/{journeyId}", name="source_journey", priority=1)
     * @ParamConverter("sourceJourney", options={"id" = "journeyId"})
     * @throws ExceptionInterface
     */
    public function sourceJourney(RepeatJourneyHelper $repeatJourneyHelper, Request $request, Journey $sourceJourney): Response
    {
        $this->setState((new RepeatJourneyState())
            ->setSubject($repeatJourneyHelper->cloneSourceJourney($sourceJourney))
            ->setIsPracticeDay($sourceJourney->getDiaryDay()->getNumber() === 0)
        );
        return $this->doWorkflow($request, RepeatJourneyState::STATE_ALT_INTRODUCTION);
    }


    protected function getState(): FormWizardStateInterface
    {
        /** @var RepeatJourneyState $state */
        $state = $this->session->get($this->getSessionKey(), new RepeatJourneyState());

        if ($state->getSubject()) {
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
        $params = array_merge($state->getPlaceRouteParameters($place));
        return new RedirectResponse($this->generateUrl('traveldiary_repeat_journey_wizard_place', $params));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('traveldiary_dashboard'));
    }
}