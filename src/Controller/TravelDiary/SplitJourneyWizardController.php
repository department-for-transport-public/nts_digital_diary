<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\PropertyMerger;
use App\FormWizard\TravelDiary\SplitJourneyState;
use App\FormWizard\TravelDiary\SplitJourneySubject;
use App\Repository\DiaryDayRepository;
use App\Utility\SplitJourneyHelper;
use Brick\Math\RoundingMode;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("/journey/{journeyId}/split-journey", name="split_journey_wizard_")
 * @Entity("sourceJourney", expr="repository.findByJourneyId(journeyId)")
 *
 * @Redirect("!impersonator_is_granted('CAN_SPLIT_JOURNEY', sourceJourney)", route="traveldiary_dashboard")
 */
class SplitJourneyWizardController extends AbstractSessionStateFormWizardController
{
    private Journey $sourceJourney;

    private const SOURCE_MERGE_PROPERTIES = [
        'diaryDay', 'startLocation', 'isStartHome', 'purpose', 'startTime', 'endTime', 'endLocation', 'isEndHome', 'stages',
    ];

    private const SOURCE_STAGE_MERGE_PROPERTIES = [
        'distanceTravelled',
        'travelTime',
    ];

    private const DESTINATION_CLONE_PROPERTIES = [
        'diaryDay', 'endLocation', 'isEndHome', 'endTime',
    ];

    private const DESTINATION_MERGE_PROPERTIES = [
        'diaryDay',
    ];

    private const STAGE_CLONE_PROPERTIES = [
        'number',
        ...Stage::COMMON_PROPERTIES,
        'isDriver', 'parkingCost',
        'ticketCost', 'boardingCount',
    ];

    public function __construct(
        protected DiaryDayRepository $diaryDayRepository,
        protected SplitJourneyHelper $splitJourneyHelper,
        FormWizardManager $formWizardManager,
        RequestStack $requestStack,
        PropertyMerger $propertyMerger,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @Route("/{place}", name="place")
     * @Route("", name="start")
     * @throws ExceptionInterface
     */
    public function index(Request $request, Journey $sourceJourney, ?string $place = null): Response
    {
        $this->sourceJourney = $sourceJourney;

        if ($place !== null) {
            $place = new Place($place);
        }

        return $this->doWorkflow($request, $place, ['originalJourney' => clone $sourceJourney]);
    }

    protected function getState(): FormWizardStateInterface
    {
        static $state = null;

        if ($state) {
            return $state;
        }

        /** @var SplitJourneyState $state */
        $state = $this->session->get($this->getSessionKey(), new SplitJourneyState());

        $existingStateSourceJourneyId = $state->getSubject()?->getSourceJourney()?->getId();
        if ($existingStateSourceJourneyId && $this->sourceJourney->getId() !== $existingStateSourceJourneyId) {
            // If the journeyId has changed from under us, reset the state...
            $state = new SplitJourneyState();
        }

        $subject = $state->getSubject();

        if (!$subject) {
            /** @var Journey $destinationJourney */
            $destinationJourney = $this->propertyMerger->clone($this->sourceJourney, self::DESTINATION_CLONE_PROPERTIES);

            /** @var Stage $sourceStage */
            $sourceStage = $this->sourceJourney->getStages()->first();

            // Halve distance and travel time
            $halfDistanceTravelled = $sourceStage->getDistanceTravelled()->getValue();
            if ($halfDistanceTravelled !== null) {
                // Round up, as rounding down could cause us to generate a zero-minute journey
                $halfDistanceTravelled = $halfDistanceTravelled->dividedBy(2, null, RoundingMode::UP);
                $sourceStage->getDistanceTravelled()->setValue($halfDistanceTravelled);
            }

            $halfTravelTime = $sourceStage->getTravelTime();
            if ($halfTravelTime !== null) {
                $halfTravelTime = intval(ceil($halfTravelTime / 2));
                $sourceStage->setTravelTime($halfTravelTime);
            }

            $destinationStage = $this->propertyMerger->clone($sourceStage, self::STAGE_CLONE_PROPERTIES);
            $destinationJourney->addStage($destinationStage);

            // Set midpoint times
            $midTime = $this->splitJourneyHelper->getMidTime($this->sourceJourney);

            $this->sourceJourney->setEndTime($midTime);
            $destinationJourney->setStartTime($midTime);

            // Clear source/dest midpoint location/isHome and purpose on dest
            $this->sourceJourney
                ->setEndTime($midTime)
                ->setEndLocation(null)
                ->setIsEndHome(false);

            $destinationPurpose = $destinationJourney->getIsEndHome() ? Journey::TO_GO_HOME : null;

            $destinationJourney
                ->setStartTime($midTime)
                ->setStartLocation(null)
                ->setIsStartHome(false)
                ->setPurpose($destinationPurpose);

            if ($this->splitJourneyHelper->whenSplitWillCrossDayBoundary($this->sourceJourney, $midTime)) {
                // Generated return journey will be in a different day...
                $diaryDay = $this->sourceJourney->getDiaryDay();
                $nextDiaryDay = $this->diaryDayRepository
                    ->findOneBy([
                        'number' => $diaryDay->getNumber() + 1,
                        'diaryKeeper' => $diaryDay->getDiaryKeeper()->getId(),
                    ]);

                if (!$nextDiaryDay) {
                    // This should never occur as the voter should stop us receiving journeys that would cross
                    // into day eight if split
                    throw new \RuntimeException('Unable to find next diary day!');
                }

                $destinationJourney->setDiaryDay($nextDiaryDay);
            }

            $state->setSubject(new SplitJourneySubject($this->sourceJourney->getPurpose(), $this->sourceJourney, $destinationJourney));
        } else {
            $sourceJourney = $this->propertyMerger->merge($this->sourceJourney, $subject->getSourceJourney(), self::SOURCE_MERGE_PROPERTIES);
            $sourceJourney = $this->propertyMerger->mergeCollection($sourceJourney, $subject->getSourceJourney(), 'stages', self::SOURCE_STAGE_MERGE_PROPERTIES);

            // Reload diaryDay from DB
            $destinationJourney = $subject->getDestinationJourney();
            $destinationJourney = $this->propertyMerger->merge($destinationJourney, $destinationJourney,self::DESTINATION_MERGE_PROPERTIES);

            $destinationJourney->getStages()->first()->setMethod($this->sourceJourney->getStages()->first()->getMethod());

            $state->setSubject(new SplitJourneySubject($this->sourceJourney->getPurpose(), $sourceJourney, $destinationJourney));
        }

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        /** @var SplitJourneyState $state */
        $state = $this->getState();

        return new RedirectResponse($this->generateUrl(
            'traveldiary_split_journey_wizard_place',
            [
                'journeyId' => $state->getSubject()->getSourceJourney()->getId(),
                'place' => $place,
            ]
        ));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('traveldiary_journey_view', [
            'journeyId' => $this->sourceJourney->getId()
        ]));
    }
}