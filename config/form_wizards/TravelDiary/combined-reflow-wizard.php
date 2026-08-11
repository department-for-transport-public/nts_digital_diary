<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\TravelDiary\CombinedReflowWizard\Journey\JourneyDetailsType;
use App\Form\TravelDiary\CombinedReflowWizard\Repeat\JourneyDetailsType as RepeatJourneyDetailsType;
use App\Form\TravelDiary\CombinedReflowWizard\Return\JourneyDetailsType as ReturnJourneyDetailsType;
use App\Form\TravelDiary\CombinedReflowWizard\Return\TargetDayType;
use App\Form\TravelDiary\CombinedReflowWizard\SourceDayJourneyType;
use App\Form\TravelDiary\CombinedReflowWizard\Stage\AddAnotherType;
use App\Form\TravelDiary\CombinedReflowWizard\Stage\MethodType;
use App\Form\TravelDiary\CombinedReflowWizard\Stage\TicketDetailsType;
use App\Form\TravelDiary\CombinedReflowWizard\WizardTypeType;
use App\Form\TravelDiary\JourneyWizard\LocationsType;
use App\Form\TravelDiary\RepeatJourneyWizard\StageDetailsType as RepeatStageDetailsType;
use App\Form\TravelDiary\ReturnJourneyWizard\StageDetailsType as ReturnStageDetailsType;
use App\Form\TravelDiary\StageWizard\DetailsType;
use App\Form\TravelDiary\StageWizard\DriverAndParkingType;
use App\Form\TravelDiary\StageWizard\VehicleType;
use App\FormWizard\TravelDiary\CombinedReflowState as State;
use App\Utility\TravelDiary\DuplicateJourneyHelper;
use Symfony\Config\Framework\Workflows\WorkflowsConfig;
use Symfony\Config\Framework\Workflows\WorkflowsConfig\PlaceConfig;
use Symfony\Config\Framework\Workflows\WorkflowsConfig\TransitionConfig;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $frameworkConfig): void {
    $config = $frameworkConfig->workflows()->workflows('form_wizard.travel_diary.combined_reflow');

    $helper = new CombinedWizardHelper($config);
    $helper
        ->createGeneralStates()
        ->createWizardTypeTransitions()

        ->createJourneyStates()
        ->createJourneyTransitions()

        ->createStageStates()
        ->createStageTransitions()

        ->createDuplicateJourneyStates(DuplicateJourneyHelper::JOURNEY_TYPE_REPEAT)
        ->createDuplicateJourneyTransitions(DuplicateJourneyHelper::JOURNEY_TYPE_REPEAT)

        ->createDuplicateJourneyStates(DuplicateJourneyHelper::JOURNEY_TYPE_RETURN)
        ->createDuplicateJourneyTransitions(DuplicateJourneyHelper::JOURNEY_TYPE_RETURN)

        ->createReturnJourneyAlternativeStartStates()
        ->createReturnJourneyAlternativeStartTransitions()
    ;
};

class CombinedWizardHelper
{
    protected string $defaultTemplate = 'travel_diary/journey/wizard_form.html.twig';

    public function __construct(protected WorkflowsConfig $config) {
        $this->config
            ->type('state_machine')
            ->supports([State::class])
            ->initialMarking(State::STATE_WIZARD_TYPE);

        $this->config->markingStore()
            ->type('method')
            ->property('place');
    }

    /****
     * General / wizard type
     ****/
    public function createGeneralStates(): static
    {
        $this->createState(
            State::STATE_WIZARD_TYPE,
            WizardTypeType::class,
            'add-journey.journey-type',
            [
                'form_data_property' => null,
                'context' => [
                    'set_journey_title_translation_parameters' => true,
                ],
            ],
        );
        $this->config->place()->name(State::STATE_FINISH);

        // ...
        $this->createState(
            State::STATE_REPEAT__SOURCE,
            '',
            'add-journey.repeat-source',
        );
        $this->createState(
            State::STATE_RETURN__SOURCE,
            '',
            'add-journey.return-source',
        );
        return $this;
    }

    public function createWizardTypeTransitions(): static
    {
        $this->config->transition()->name('type__add')
            ->from(State::STATE_WIZARD_TYPE)
            ->to(State::STATE_STANDARD__LOCATIONS)
            ->guard(sprintf('subject.getWizardType() == "%s" or subject.getWizardType() == null', State::WIZARD_TYPE__ADD));

        $this->config->transition()->name('type__repeat')
            ->from(State::STATE_WIZARD_TYPE)
            ->to(State::STATE_REPEAT__SOURCE)
            ->guard(sprintf('subject.getWizardType() == "%s"', State::WIZARD_TYPE__REPEAT));

        $this->config->transition()->name('type__return')
            ->from(State::STATE_WIZARD_TYPE)
            ->to(State::STATE_RETURN__SOURCE)
            ->guard(sprintf('subject.getWizardType() == "%s"', State::WIZARD_TYPE__RETURN));

        return $this;
    }


    /****
     * Journeys
     ****/
    public function createJourneyStates(): static
    {
        $this->createState(
            State::STATE_STANDARD__LOCATIONS,
            LocationsType::class,
            'add-journey.locations',
            [
                // isEditing() matters as well as the id: the add flow persists the
                // journey partway through (standard__details__stage_method carries
                // persist), so an id alone no longer distinguishes "editing an
                // existing journey via a Change link" from "midway through adding
                // stages to one".
                'is_valid_alternative_start_place' => 'state.isEditing() and !isEmpty(state.getSubject().getId())',
            ]
        );
        $this->createState(
            State::STATE_STANDARD__DETAILS,
            JourneyDetailsType::class,
            'add-journey.journey-details',
            [
                'form_data_property' => null,
                'is_valid_alternative_start_place' => 'state.isEditing() and !isEmpty(state.getSubject().getId())',
            ],
        );
        return $this;
    }

    public function createJourneyTransitions(): static
    {
        $finishMetadata =  [
            'persist' => true,
            'redirect_route' => [
                'name' => 'traveldiary_journey_view',
                'parameters' => ['journeyId' => 'subject.id'],
            ],
        ];

        // Add/edit a journey
        $this->config->transition()->name('standard__locations__details')
            ->from(State::STATE_STANDARD__LOCATIONS)
            ->to(State::STATE_STANDARD__DETAILS);
        $this->config->transition()->name('standard__details__stage_method')
            ->from(State::STATE_STANDARD__DETAILS)
            ->to(State::STATE_STANDARD__STAGE_METHOD)
            ->guard('!subject.isEditing()')
            ->metadata('persist', true);
        $transition = $this->config->transition()->name('standard__details__finish')
            ->from(State::STATE_STANDARD__DETAILS)
            ->to(State::STATE_FINISH)
            ->guard('subject.isEditing()');
        $this->applyMetadata($transition, $finishMetadata);

        return $this;
    }

    /****
     * Stages
     ****/
    public function createStageStates(): static
    {
        $originalDefaultTemplate = $this->defaultTemplate;
        $this->defaultTemplate = 'travel_diary/stage/wizard_form.html.twig';

        // Common
        $this->createState(
            // an event subscriber is used to clear history before upon entering this state
            State::STATE_STANDARD__STAGE_METHOD,
            MethodType::class,
            'add-journey.stage-method',
            [
                'context' => ['set_stage_title_translation_parameters' => true],
                'is_valid_alternative_start_place' => 'state.getStage() and isEmpty(state.getStage().getId())',
                'form_data_property' => 'stage',
            ]
        );
        $this->createState(
            State::STATE_STANDARD__STAGE_DETAILS,
            DetailsType::class,
            'add-journey.stage-details',
            [
                'is_valid_alternative_start_place' => 'state.getStage() and !isEmpty(state.getStage().getId())',
                'form_data_property' => 'stage',
                'form_options' => ['wizard_source' => 'reflow'],
            ]
        );

        // private stage
        $this->createState(
            State::STATE_STANDARD__STAGE_VEHICLE,
            VehicleType::class,
            'add-journey.stage-vehicle',
            [
                'is_valid_alternative_start_place' => 'state.getStage() and !isEmpty(state.getStage().getId()) and state.getStageMethodType() === "private"',
                'form_data_property' => 'stage',
                'form_options' => ['vehicle_label' => 'add-journey.stage-vehicle.page-title'],
            ]

        );
        $this->createState(
            State::STATE_STANDARD__STAGE_DRIVER_AND_PARKING,
            DriverAndParkingType::class,
            'add-journey.stage-driver-and-parking',
            ['form_data_property' => 'stage']
        );

        //public stage
        $this->createState(
            State::STATE_STANDARD__STAGE_TICKET_DETAILS,
            TicketDetailsType::class,
            'add-journey.stage-ticket-details',
            [
                'is_valid_alternative_start_place' => 'state.getStage() and !isEmpty(state.getStage().getId()) && state.getStageMethodType() === "public"',
                'form_data_property' => 'stage',
            ]
        );

        // Add another stage
        $this->createState(
            State::STATE_STANDARD__STAGE_ADD_ANOTHER,
            AddAnotherType::class,
            'add-journey.stage-add-another',
            [
                'form_data_property' => false,
                'form_options' => ['journey' => 'subject'],
            ],
        );

        $this->defaultTemplate = $originalDefaultTemplate;
        return $this;
    }

    public function createStageTransitions(): static
    {
        $redirectMetadata =  [
            'redirect_route' => [
                'name' => 'traveldiary_journey_view',
                'parameters' => ['journeyId' => 'subject.id'],
                'hash' => 'stage-{stage}',
                'hash_parameters' => ['{stage}' => 'stage.number']
            ],
        ];

        // Stage method
        $this->config->transition()->name('standard__stage_method__stage_details')
            ->from(State::STATE_STANDARD__STAGE_METHOD)
            ->to(State::STATE_STANDARD__STAGE_DETAILS);

        // "Other" stage
        $this->config->transition()->name('standard__stage_details__stage_add_another')
            ->from(State::STATE_STANDARD__STAGE_DETAILS)
            ->to(State::STATE_STANDARD__STAGE_ADD_ANOTHER)
            ->guard('subject.isTypeOther() && !subject.isEditing()')
            ->metadata('persist', true);

        // "Private" stage
        $this->config->transition()->name('standard__stage_details__stage_vehicle')
            ->from(State::STATE_STANDARD__STAGE_DETAILS)
            ->to(State::STATE_STANDARD__STAGE_VEHICLE)
            ->guard('subject.isTypePrivate() && !subject.isEditing()');
        $this->config->transition()->name('standard__stage_vehicle__stage_driver_and_parking')
            ->from(State::STATE_STANDARD__STAGE_VEHICLE)
            ->to(State::STATE_STANDARD__STAGE_DRIVER_AND_PARKING)
            ->guard('subject.isAdultDiary()');
        $this->config->transition()->name('standard__stage_driver_and_parking__stage_add_another')
            ->from(State::STATE_STANDARD__STAGE_DRIVER_AND_PARKING)
            ->to(State::STATE_STANDARD__STAGE_ADD_ANOTHER)
            ->guard('!subject.isEditing()')
            ->metadata('persist', true);
        $this->config->transition()->name('standard__stage_vehicle__stage_add_another')
            ->from(State::STATE_STANDARD__STAGE_VEHICLE)
            ->to(State::STATE_STANDARD__STAGE_ADD_ANOTHER)
            ->guard('!subject.isAdultDiary() && !subject.isEditing()')
            ->metadata('persist', true);

        // "Public" stage
        $this->config->transition()->name('standard__stage_details__stage_ticket_details')
            ->from(State::STATE_STANDARD__STAGE_DETAILS)
            ->to(State::STATE_STANDARD__STAGE_TICKET_DETAILS)
            ->guard('subject.isTypePublic() && !subject.isEditing()');
//        $this->config->transition()->name('standard__stage_ticket_type__stage_ticket_cost')
//            ->from(State::STATE_STANDARD__STAGE_TICKET_DETAILS)
//            ->to(State::STATE_STANDARD__STAGE_TICKET_COST_AND_BOARDINGS);
        $this->config->transition()->name('standard__stage_ticket_cost__stage_add_another')
            ->from(State::STATE_STANDARD__STAGE_TICKET_DETAILS)
            ->to(State::STATE_STANDARD__STAGE_ADD_ANOTHER)
            ->guard("!subject.isEditing()")
            ->metadata('persist', true);

        // Add another
        $transition = $this->config->transition()->name('standard__stage_finish')
            ->from([
                State::STATE_STANDARD__STAGE_DETAILS,
                State::STATE_STANDARD__STAGE_DRIVER_AND_PARKING,
                State::STATE_STANDARD__STAGE_TICKET_DETAILS
            ])
            ->to(State::STATE_FINISH)
            ->guard("subject.isEditing()")
            ->metadata('persist', true);
        $this->applyMetadata($transition, $redirectMetadata);
        $this->config->transition()->name('standard__stage_add_another__stage_method')
            ->from(State::STATE_STANDARD__STAGE_ADD_ANOTHER)
            ->to(State::STATE_STANDARD__STAGE_METHOD)
            ->guard("!isFormDataPropertySameAs('[is_final_stage]', true)");
        $transition = $this->config->transition()->name('standard__stage_add_another__finish')
            ->from(State::STATE_STANDARD__STAGE_ADD_ANOTHER)
            ->to(State::STATE_FINISH)
            ->guard("isFormDataPropertySameAs('[is_final_stage]', true)");
        $this->applyMetadata($transition, $redirectMetadata);

        return $this;
    }



    /****
     * Repeat journey
     ****/
    public function createDuplicateJourneyStates(string $journeyType): static
    {
        $originalDefaultTemplate = $this->defaultTemplate;
        $this->defaultTemplate = "travel_diary/{$journeyType}_journey/wizard_form.html.twig";

        // Common
        $this->createState(
            State::DUPLICATE_JOURNEY_STATES[$journeyType]['source'],
            SourceDayJourneyType::class,
            "{$journeyType}.source",
            [
                'form_data_property' => null,
                'form_options' => ['journey_type' => $journeyType],
            ]
        );
        $this->createState(
            State::DUPLICATE_JOURNEY_STATES[$journeyType]['journey-details'],
            match($journeyType) {
                DuplicateJourneyHelper::JOURNEY_TYPE_REPEAT => RepeatJourneyDetailsType::class,
                DuplicateJourneyHelper::JOURNEY_TYPE_RETURN => ReturnJourneyDetailsType::class,
            },
            "{$journeyType}.journey-details",
            [
                'is_valid_alternative_start_place' => '!isEmpty(state.sourceJourneyId)',
                'form_data_property' => null,
            ]
        );
        $this->createState(
            State::DUPLICATE_JOURNEY_STATES[$journeyType]['stage-details'],
            match($journeyType) {
                DuplicateJourneyHelper::JOURNEY_TYPE_REPEAT => RepeatStageDetailsType::class,
                DuplicateJourneyHelper::JOURNEY_TYPE_RETURN => ReturnStageDetailsType::class,
            },
            "{$journeyType}.stage-details",
            [
                'form_data_property' => 'contextStage',
                'context' => [
                    'set_translation_key_from_state' => true,
                ],
            ]
        );

        $this->defaultTemplate = $originalDefaultTemplate;
        return $this;
    }

    public function createDuplicateJourneyTransitions(string $journeyType): static
    {
        $finishMetadata =  [
            'persist' => true,
            'redirect_route' => [
                'name' => 'traveldiary_journey_view',
                'parameters' => ['journeyId' => 'subject.id'],
            ],
        ];

        $this->config->transition()->name("{$journeyType}__source__journey_details")
            ->from(State::DUPLICATE_JOURNEY_STATES[$journeyType]['source'])
            ->to(State::DUPLICATE_JOURNEY_STATES[$journeyType]['journey-details']);;
        $transition = $this->config->transition()->name("{$journeyType}__journey_details__finish")
            ->from(State::DUPLICATE_JOURNEY_STATES[$journeyType]['journey-details'])
            ->to(State::STATE_FINISH)
            ->guard('(subject.isSameStages ?? true) && subject.getStageCount() == 0');
        $this->applyMetadata($transition, $finishMetadata);
        $this->config->transition()->name("{$journeyType}__journey_details__stage_details")
            ->from(State::DUPLICATE_JOURNEY_STATES[$journeyType]['journey-details'])
            ->to(State::DUPLICATE_JOURNEY_STATES[$journeyType]['stage-details'])
            ->guard('(subject.isSameStages ?? true) && subject.getStageCount() > 0')
            ->metadata('transition_context', ['stageNumber' => 1]);
        $this->config->transition()->name("{$journeyType}__journey_details__fresh_stage_method")
            ->from(State::DUPLICATE_JOURNEY_STATES[$journeyType]['journey-details'])
            ->to(State::STATE_STANDARD__STAGE_METHOD)
            // Event subscriber is used to clear stages and history during this transition
            ->guard('!(subject.isSameStages ?? true)');

        $this->config->transition()->name("{$journeyType}__stage_details__next_stage_details")
            ->from(State::DUPLICATE_JOURNEY_STATES[$journeyType]['stage-details'])
            ->to(State::DUPLICATE_JOURNEY_STATES[$journeyType]['stage-details'])
            ->guard('subject.getStageCount() > subject.getStageNumber()')
            ->metadata('transition_context', ['stageNumber' => 'nextStageNumber']);
        $transition = $this->config->transition()->name("{$journeyType}__stage_details__finish")
            ->from(State::DUPLICATE_JOURNEY_STATES[$journeyType]['stage-details'])
            ->to(State::STATE_FINISH)
            ->guard('subject.getStageCount() <= subject.getStageNumber()');
        $this->applyMetadata($transition, $finishMetadata);

        return $this;
    }


    /****
     * Return journey (alt)
     ****/
    public function createReturnJourneyAlternativeStartStates(): static
    {
        $originalDefaultTemplate = $this->defaultTemplate;
        $this->defaultTemplate = "travel_diary/return_journey/wizard_form.html.twig";

        $this->createState(
            State::STATE_RETURN__TARGET_DAY,
            TargetDayType::class,
            "return.source",
            [
                'form_data_property' => null,
                // Only a valid entry once the source journey has been recorded on
                // the state (the wizard's start path sets it). Without this a fresh
                // or stale request to target-day - e.g. the browser back button
                // onto the return wizard's first page after completion - would
                // render it directly and 500 querying with a null source journey.
                // Gating it here routes such requests through the bounce-back guard.
                'is_valid_alternative_start_place' => '!isEmpty(state.sourceJourneyId)',
            ]
        );

        $this->defaultTemplate = $originalDefaultTemplate;
        return $this;
    }

    public function createReturnJourneyAlternativeStartTransitions(): static
    {
        $this->config->transition()->name("return__target_day__journey_details")
            ->from(State::STATE_RETURN__TARGET_DAY)
            ->to(State::STATE_RETURN__JOURNEY_DETAILS);

        return $this;
    }

    /****
     * Misc
     ****/
    protected function createState(string $name, ?string $formClass, ?string $translationPrefixPart = null, array $additionalMetadata = [], string $template = null): PlaceConfig
    {
        $place =  $this->config->place()->name($name);
        $this->applyMetadata($place, array_merge($additionalMetadata, [
            'template' => $template ?? $this->defaultTemplate,
            'view_data' => array_merge($translationPrefixPart ? [
                'translation_prefix' => $translationPrefixPart,
                'translation_domain' => 'travel-diary'
            ] : [], $additionalMetadata['view_data'] ?? []),
            'form_class' => $formClass,
        ]));
        return $place;
    }

    protected function applyMetadata(PlaceConfig|WorkflowsConfig|TransitionConfig $config, array $metadata): void
    {
        foreach($metadata as $key => $value) {
            $config->metadata($key, $value);
        }
    }
}
