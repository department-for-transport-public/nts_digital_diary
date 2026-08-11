<?php

namespace App\FormWizard\TravelDiary;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Utility\DateTimeFormats;
use App\Utility\TravelDiary\DuplicateJourneyHelper;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

class CombinedReflowState extends AbstractDuplicateJourneyState
{
    // Wizard types
    public const string WIZARD_TYPE__ADD = 'add';
    public const string WIZARD_TYPE__EDIT = 'edit';
    public const string WIZARD_TYPE__REPEAT = 'repeat';
    public const string WIZARD_TYPE__RETURN = 'return';


    // Wizard type state
    public const string STATE_WIZARD_TYPE = 'wizard-type';
    public const string STATE_FINISH = 'finish';

    // New journey states
    public const string STATE_STANDARD__LOCATIONS = 'standard--locations';
    public const string STATE_STANDARD__DETAILS = 'standard--details';
    public const string _STATE_STANDARD__TIMES = 'standard--times';
    public const string _STATE_STANDARD__PURPOSE = 'standard--purpose';

    // New stage states
    public const string STATE_STANDARD__STAGE_METHOD = 'standard--stage-method';
    public const string STATE_STANDARD__STAGE_DETAILS = 'standard--stage-details';
    public const string STATE_STANDARD__STAGE_VEHICLE = 'standard--stage-vehicle';
    public const string STATE_STANDARD__STAGE_DRIVER_AND_PARKING = 'standard--stage-driver-and-parking';
    public const string STATE_STANDARD__STAGE_TICKET_DETAILS = 'standard--stage-ticket-details';
//    public const string STATE_STANDARD__STAGE_TICKET_COST_AND_BOARDINGS = 'standard--stage-ticket-cost-and-boardings';
    public const string STATE_STANDARD__STAGE_ADD_ANOTHER = 'standard--stage-add-another';


    // repeat journey states
    public const string STATE_REPEAT__SOURCE = 'repeat--source';
    public const string STATE_REPEAT__JOURNEY_DETAILS = 'repeat--journey-details';
    public const string STATE_REPEAT__STAGE_DETAILS = 'repeat--stage-details';

    // return journey states
    public const string STATE_RETURN__SOURCE = 'return--source';
    public const string STATE_RETURN__JOURNEY_DETAILS = 'return--journey-details';
    public const string STATE_RETURN__STAGE_DETAILS = 'return--stage-details';
    public const string STATE_RETURN__TARGET_DAY = 'return--target-day';

    public const array DUPLICATE_JOURNEY_STATES = [
        DuplicateJourneyHelper::JOURNEY_TYPE_REPEAT => [
            'source' => self::STATE_REPEAT__SOURCE,
            'journey-details' => self::STATE_REPEAT__JOURNEY_DETAILS,
            'stage-details' => self::STATE_REPEAT__STAGE_DETAILS,
        ],
        DuplicateJourneyHelper::JOURNEY_TYPE_RETURN => [
            'source' => self::STATE_RETURN__SOURCE,
            'journey-details' => self::STATE_RETURN__JOURNEY_DETAILS,
            'stage-details' => self::STATE_RETURN__STAGE_DETAILS,
        ],
    ];


//    protected ?Journey $journey = null;
    protected ?Stage $stage = null;
    protected ?string $wizardType = null;

    #[Assert\NotNull(message: 'wizard.repeat-journey.source-day.not-null', groups: ['wizard.duplicate-journey.source-day'])]
    public ?int $sourceDayNumber = null;

    #[Assert\NotNull(message: 'wizard.repeat-journey.source-journey.not-null', groups: ['wizard.duplicate-journey.source-journey'])]
    public ?string $sourceJourneyId = null;

    #[Assert\NotNull(message: 'common.choice.invalid', groups: ['wizard.repeat-journey.same-stages', 'wizard.return-journey.same-stages'])]
    public ?bool $isSameStages = null;

//    public function getSubject(): ?Journey
//    {
//        return dump(match ($this->getSubjectClass()) {
//            Stage::class => $this->stage->getJourney() ?? $this->subject    ,
//            default => $this->subject,
//        });
//    }



//    /**
//     * @param null|Journey|Stage $subject
//     */
//    public function setSubject($subject): self
//    {
//        switch(true) {
//            case ($subject instanceof Stage):
//                $this->stage = $subject;
//                break;
//
//            default:
//                $this->journey = $subject;
//        }
//        return $this;
//    }

//    public function getSubjectClass(): string
//    {
//        return match(true) {
//            str_starts_with($this->getPlace()->name ?? null, 'standard--stage-') => Stage::class,
//            default => Journey::class
//        };
//    }

    public function getStage(): ?Stage
    {
        return $this->stage;
    }

    public function setStage(?Stage $stage): static
    {
        $this->stage = $stage;
        $this->subject?->addStage($stage);
        return $this;
    }

    public function getWizardType(): ?string
    {
        return $this->wizardType;
    }

    public function setWizardType(?string $wizardType): void
    {
        $this->wizardType = $wizardType;
    }

    public function getStageMethodType(): ?string
    {
        return $this->stage?->getMethod()?->getType();
    }

    public function createNewStage(): Stage
    {
        $this->stage = (new Stage())->setNumber($this->getNextStageNumberForJourney());
        $this->subject->addStage($this->stage);
        return $this->stage;
    }

    /**
     * The inverse of createNewStage(). Only ever discards the stage this wizard
     * created and has not yet persisted, so a duplicate-journey flow's unsaved
     * stages (built up front by DuplicateJourneyHelper) are left alone.
     */
    public function discardInProgressStage(): void
    {
        if ($this->stage && !$this->stage->getId()) {
            $this->subject?->removeStage($this->stage);
        }
        $this->stage = null;
    }

    public function getNextStageNumberForJourney(): int
    {
        return $this->subject?->getStages()?->count() + 1;
    }

    public function clearHistoryForNewStage(): bool
    {
        // Drop the just-completed stage's pages from history so the new stage
        // starts fresh, but keep the add-another place: it is the genuine
        // "previous" page, so the browser back button from the new stage's
        // method page returns there instead of falling back to the wizard's
        // initial place (wizard-type). Retaining the stage-method place instead
        // made setPlace() match and truncate the history to empty.
        $this->placeHistory = array_values(array_filter(
            $this->placeHistory,
            fn($place) => $place->name === self::STATE_STANDARD__STAGE_ADD_ANOTHER,
        ));
        $this->stage = null;
        return true;
    }

    public function isAdultDiary(): bool
    {
        return $this->getStage()->getJourney()->getDiaryDay()->getDiaryKeeper()->getIsAdult() ?? false;
    }

    public function isTypeOther(): bool
    {
        return $this->stage->getMethod()->getType() === Method::TYPE_OTHER;
    }

    public function isTypePrivate(): bool
    {
        return $this->stage->getMethod()->getType() === Method::TYPE_PRIVATE;
    }

    public function isTypePublic(): bool
    {
        return $this->stage->getMethod()->getType() === Method::TYPE_PUBLIC;
    }

    public function isEditing(): bool
    {
        return $this->getWizardType() === self::WIZARD_TYPE__EDIT;
    }


    public function getStageDetailsTitle(): TranslatableMessage
    {
        return new TranslatableMessage('return-or-repeat.stage-details.page-title', [
            'number' => $this->getStageNumber(),
            // as this is part of place metadata, this was throwing an exception when clicking back after finishing, so we need to check
            'method' => $this->getContextStage() ? $this->getContextStage()->getMethodForDisplay(true) : null,
        ], 'travel-diary');
    }

    public function clearStages(): void
    {
        $this->subject->getStages()->clear();
    }

    public function getJourneyDayDateWithDOW(): ?string
    {
        return $this->subject->getDiaryDay()?->getDate()?->format(DateTimeFormats::DATE_WITH_DOW);
    }

    public function isEditingSingleStageJourney(): bool
    {
        return
            $this->isEditing()
            && $this->subject->getStages()->count() === 1;
    }
}