<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\PropertyMerger;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("/day-{dayNumber}/repeat-journey", name="repeat_journey_v2_wizard_")
 * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
 */
class RepeatJourneyWizardController extends AbstractSessionStateFormWizardController
{
    private int $targetDayNumber;

    public function __construct(
        FormWizardManager $formWizardManager,
        RequestStack $requestStack,
        PropertyMerger $propertyMerger,
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }


    /**
     * @Route(name="start")
     * @Route("/{place}/{stageNumber}", name="place")
     * @throws ExceptionInterface
     */
    public function index(Request $request, int $dayNumber, ?string $place = null, $stageNumber = 0): Response
    {
        $this->targetDayNumber = $dayNumber;
        if ($place !== null) $place = new Place($place, $stageNumber > 0 ? ['stageNumber' => intval($stageNumber)] : []);

        $getAdditionalViewData = function(RepeatJourneyState $state) {
            $sourceJourney = $state->sourceJourneyId !== null ?
                $this->entityManager->find(Journey::class, $state->sourceJourneyId) :
                null;

            return ['sourceJourney' => $sourceJourney];
        };

        return $this->doWorkflow($request, $place, $getAdditionalViewData);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var RepeatJourneyState $state */
        $state = $this->session->get($this->getSessionKey(), (new RepeatJourneyState())->setTargetDayNumber($this->targetDayNumber));

        if ($state->getSubject()) {
            $baseEntity = $state->getSubject();

            $baseEntity->setDiaryDay($this->getDiaryKeeper()->getDiaryDayByNumber($this->targetDayNumber));

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
        $params = array_merge(['dayNumber' => $this->targetDayNumber], $state->getPlaceRouteParameters($place));
        return new RedirectResponse($this->generateUrl('traveldiary_repeat_journey_v2_wizard_place', $params));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $this->targetDayNumber]));
    }

    protected function getDiaryKeeper(): DiaryKeeper
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user->getDiaryKeeper();
    }
}