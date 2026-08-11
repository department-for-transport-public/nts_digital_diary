<?php

namespace App\Controller\TravelDiary\Wizards\CombinedReflow;

use App\Attribute\Redirect;
use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Utility\DateTimeFormats;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Metadata\PlaceMetadata;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

#[Redirect("!is_granted('EDIT_DIARY')", route: "traveldiary_dashboard")]
abstract class AbstractCombinedReflowController extends AbstractSessionStateFormWizardController
{
    protected Journey $referenceJourney;
    protected Stage $referenceStage;
    protected ?string $dayNumber = null;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        FormWizardManager                $formWizardManager,
        RequestStack                     $requestStack,
        PropertyMerger                   $propertyMerger
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @return CombinedReflowState
     */
    protected function getState(): FormWizardStateInterface
    {
        /** @var CombinedReflowState $state */
        $state = $this->session->get($this->getSessionKey(), new CombinedReflowState());
        if (str_starts_with($state->getPlace()?->name ?? '', 'standard--stage-')) {
            $this->reloadStage($state);
        } else {
            $this->reloadJourney($state);
        }

        return $state;
    }

    /**
     * @param CombinedReflowState $state
     */
    protected function reloadStage(FormWizardStateInterface $state): void
    {
        if ($this->referenceJourney ?? false) {
            $state->setSubject($this->referenceJourney);
        }

        // we need to reload the journey to ensure it and the day are managed by the EM
        if ($state->getSubject()?->getId()) {
            $state->setSubject($this->entityManager->find(Journey::class, $state->getSubject()->getId()));
        } else {
            $state->getSubject()?->setDiaryDay($this->getDiaryKeeper()->getDiaryDayByNumber($state->getSubject()->getDiaryDay()->getNumber()));
        }

        if (($baseEntity = $state->getStage()) && $baseEntity instanceof Stage) {
            if ($baseEntity->getId()) {
                $baseEntity = $this->entityManager->find(Stage::class, $baseEntity->getId());
            }

            $baseEntity = $this->propertyMerger->merge($baseEntity, $state->getStage(), Stage::MERGE_PROPERTIES);
            $state->setStage($baseEntity);
        } elseif ($this->referenceStage ?? false) {
            $state->setStage($this->referenceStage);
        } elseif (($inProgressStage = $state->getSubject()?->getStages()->last()) && !$inProgressStage->getId()) {
            // getState() is called several times per request (doWorkflow +
            // getAdditionalViewData), and on a fresh session each call builds a
            // new state with a null stage. A journey loaded from the EM only
            // holds persisted stages, so a trailing stage with no id can only be
            // the in-progress stage a previous getState() call already created on
            // this managed journey — reuse it rather than appending another.
            $state->setStage($inProgressStage);
        } else {
            $state->createNewStage();
        }

        if (!$state->getSubject()) {
            $state->setSubject($state->getStage()->getJourney());
        }
    }

    /**
     * @param CombinedReflowState $state
     */
    protected function reloadJourney(FormWizardStateInterface $state): void
    {
        // We are on a journey place, so any stage this wizard had in progress is
        // out of scope. It must go before the merge below: a journey loaded from
        // the EM holds only persisted stages, so leaving an unsaved one on the
        // session journey makes the overlay outgrow the base, and mergeCollection
        // walks the overlay's keys against the base and hands PropertyMerger a
        // null entity. That throws, and because the session place stays on a
        // journey place, every subsequent request throws too.
        $state->discardInProgressStage();

        if (($baseEntity = $state->getSubject()) && $baseEntity instanceof Journey) {
            if ($baseEntity->getId()) {
                $baseEntity = $this->entityManager->find(Journey::class, $baseEntity->getId());
            }

            // needs to be a null check, as the practice day (0) gives a falsy result here
            if ($this->dayNumber !== null) {
                $baseEntity->setDiaryDay($this->getDiaryKeeper()->getDiaryDayByNumber($this->dayNumber));
            }

            // Seems odd to be merging it with itself, but it reloads relations from the DB, so that they are managed by EM
            $baseEntity = $this->propertyMerger->merge($baseEntity, $state->getSubject(), Journey::MERGE_PROPERTIES);
            $baseEntity = $this->propertyMerger->mergeCollection($baseEntity, $state->getSubject(), 'stages', Stage::MERGE_PROPERTIES);
            $state->setSubject($baseEntity);
        } else {
            $state->setSubject($this->referenceJourney ?? new Journey());
        }
    }

    /**
     * When a new stage is started the wizard history is cleared (see
     * CombinedReflowSubscriber::enteringStageMethod). If the user then presses
     * the browser back button, doWorkflow finds no history and falls back to the
     * wizard's initial place — wizard-type. That is not a valid destination once
     * a journey exists: the add-journey wizard silently restarts, the add-stage
     * wizard has no wizard-type page at all and 500s, and the journey/stage-keyed
     * wizards (return/edit) 500 building a title from a day number they never
     * carry. In every case send the user to the journey view instead.
     *
     * Subclasses point the bounce at the right journey by overriding
     * getBounceBackJourneyId().
     */
    protected function bounceBackRedirectResponse(?Place $place): ?RedirectResponse
    {
        if ($place?->name !== CombinedReflowState::STATE_WIZARD_TYPE) {
            return null;
        }

        $journeyId = $this->getBounceBackJourneyId();

        return $journeyId
            ? new RedirectResponse($this->generateUrl('traveldiary_journey_view', ['journeyId' => $journeyId]))
            : null;
    }

    /**
     * The journey to bounce a wizard-type fallback back to. Defaults to the
     * journey being built (add wizards); wizards keyed by an existing journey or
     * stage override this to point at that entity, which is always available where
     * a fresh add-wizard session's subject would have no id yet.
     */
    protected function getBounceBackJourneyId(): ?string
    {
        return $this->getState()->getSubject()?->getId();
    }

    protected function getDiaryDay(): DiaryDay
    {
        /** @var User $user */
        $user = $this->getUser();

        $diaryDay = $this->entityManager
            ->getRepository(DiaryDay::class)
            ->findOneBy([
                'diaryKeeper' => $user->getDiaryKeeper(),
                'number' => $this->dayNumber
            ]);

        if (!$diaryDay) {
            $user->getDiaryKeeper()->addDiaryDay($diaryDay = (new DiaryDay())->setNumber($this->dayNumber));
            $this->entityManager->flush();
        }

        return $diaryDay;
    }

    protected function getDiaryKeeper(): DiaryKeeper
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user->getDiaryKeeper();
    }

    public function getAdditionalViewData(CombinedReflowState $state, PlaceMetadata $placeMetadata): array {
        $sourceJourney = $state->sourceJourneyId !== null ?
            $this->entityManager->find(Journey::class, $state->sourceJourneyId) :
            null;

        $data = ['sourceJourney' => $sourceJourney];

        if ($placeMetadata->hasContextEntry('set_translation_key_from_state')) {
            $data['title_translation_key'] = $state->getStageDetailsTitle();
        }

        if ($placeMetadata->hasContextEntry('source_form_journey_type')) {
            $data['journey_type'] = $placeMetadata->getContextEntry('source_form_journey_type');
        }

        $data['title_translation_parameters'] = [];
        if ($placeMetadata->hasContextEntry('set_journey_title_translation_parameters')) {
            $data['title_translation_parameters'] = [
                'date' => $this->getDiaryDay()->getDate()->format(DateTimeFormats::DATE_WITH_DOW),
                'dayNumber' => $this->dayNumber,
            ];
        }
        if ($placeMetadata->hasContextEntry('set_stage_title_translation_parameters')) {
            $data['title_translation_parameters'] = [
                'dayNumber' => $this->getState()->getSubject()->getDiaryDay()->getNumber(),
                'stageNumber' => $this->getState()->getStage()->getNumber(),
                'startLocation' => $this->getState()->getSubject()->getStartLocationForDisplay(),
            ];
        }

        $data['title_translation_parameters']['editingSingleStageJourney'] = $state->isEditingSingleStageJourney();

        return $data;
    }
}