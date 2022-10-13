<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\TravelDiary\RepeatJourneyWizard\PurposeType;
use App\Form\TravelDiary\RepeatJourneyWizard\TargetDayType;
use App\Form\TravelDiary\RepeatJourneyWizard\TimesType;
use App\Form\TravelDiary\RepeatJourneyWizard\SourceDayType;
use App\Form\TravelDiary\RepeatJourneyWizard\SourceJourneyType;
use App\Form\TravelDiary\RepeatJourneyWizard\StageDetailsType;
use App\FormWizard\TravelDiary\RepeatJourneyState as State;

return static function (ContainerConfigurator $container) {
    $editFinishMetadata =  [
        'persist' => true,
        'redirect_route' => [
            'name' => 'traveldiary_journey_view',
            'parameters' => ['journeyId' => 'subject.id'],
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'form_wizard.travel_diary.repeat_journey' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_FULL_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    td_place(State::STATE_FULL_INTRODUCTION,null, 'repeat-journey.full-introduction', [], 'travel_diary/repeat_journey/wizard_full_introduction.html.twig'),
                    td_place(State::STATE_SELECT_SOURCE_DAY,SourceDayType::class, 'repeat-journey.select-source-day', [
                        'form_data_property' => null,
                        'form_options' => ['is_practice' => 'isPracticeDay'],
                    ]),
                    td_place(State::STATE_SELECT_SOURCE_JOURNEY,SourceJourneyType::class, 'repeat-journey.select-source-journey', [
                        'form_data_property' => null,
                    ]),
                    td_place(State::STATE_ALT_INTRODUCTION,null, 'repeat-journey.alt-introduction', [
                        'translation_parameters' => [
                            'startLocation' => 'subject.startLocationForDisplay',
                            'endLocation' => 'subject.endLocationForDisplay',
                        ],
                        'is_valid_alternative_start_place' => true
                    ], 'travel_diary/repeat_journey/wizard_alt_introduction.html.twig'),
                    td_place(State::STATE_SELECT_TARGET_DAY,TargetDayType::class, 'repeat-journey.target-day', [
                        'form_options' => ['is_practice' => 'isPracticeDay'],
                    ]),
                    td_place(State::STATE_PURPOSE,PurposeType::class, 'repeat-journey.purpose'),
                    td_place(State::STATE_ADJUST_TIMES,TimesType::class, 'repeat-journey.adjust-times'),
                    td_place(State::STATE_ADJUST_STAGE_DETAILS,StageDetailsType::class, 'repeat-journey.stage-details', [
                        'form_data_property' => 'contextStage',
                        'view_data' => [
                            'title_translatable_message' => 'stageDetailsTitle',
                        ],
                    ]),
                    ['name' => State::STATE_FINISH],
                ],

                'transitions' => [
                    td_transition(
                        State::TRANSITION_FULL_INTRO_TO_SOURCE_DAY,
                        State::STATE_FULL_INTRODUCTION,
                        State::STATE_SELECT_SOURCE_DAY,
                    ),
                    td_transition(
                        State::TRANSITION_DAY_TO_SOURCE_JOURNEY,
                        State::STATE_SELECT_SOURCE_DAY,
                        State::STATE_SELECT_SOURCE_JOURNEY,
                    ),
                    td_transition(
                        State::TRANSITION_SOURCE_JOURNEY_TO_TARGET_DAY,
                        State::STATE_SELECT_SOURCE_JOURNEY,
                        State::STATE_SELECT_TARGET_DAY,
                    ),
                    td_transition(
                        State::TRANSITION_ALT_INTRO_TO_TARGET_DAY,
                        State::STATE_ALT_INTRODUCTION,
                        State::STATE_SELECT_TARGET_DAY,
                    ),
                    td_transition(
                        State::TRANSITION_TARGET_DAY_TO_PURPOSE,
                        State::STATE_SELECT_TARGET_DAY,
                        State::STATE_PURPOSE,
                    ),
                    td_transition(
                        State::TRANSITION_PURPOSE_TO_TIMES,
                        State::STATE_PURPOSE,
                        State::STATE_ADJUST_TIMES,
                    ),
                    td_transition(
                        State::TRANSITION_TIMES_TO_FINISH,
                        State::STATE_ADJUST_TIMES,
                        State::STATE_FINISH,
                        'subject.getStageCount() == 0',
                        $editFinishMetadata
                    ),
                    td_transition(
                        State::TRANSITION_TIMES_TO_STAGE,
                        State::STATE_ADJUST_TIMES,
                        State::STATE_ADJUST_STAGE_DETAILS,
                        'subject.getStageCount() > 0',
                        ['context' => [
                            'stageNumber' => 1
                        ]]
                    ),
                    td_transition(
                        State::TRANSITION_STAGE_TO_NEXT_STAGE,
                        State::STATE_ADJUST_STAGE_DETAILS,
                        State::STATE_ADJUST_STAGE_DETAILS,
                        'subject.getStageCount() > subject.getStageNumber()',
                        ['context' => [
                            'stageNumber' => 'nextStageNumber'
                        ]]
                    ),
                    td_transition(
                        State::TRANSITION_STAGE_TO_FINISH,
                        State::STATE_ADJUST_STAGE_DETAILS,
                        State::STATE_FINISH,
                        'subject.getStageCount() <= subject.getStageNumber()',
                        $editFinishMetadata
                    ),
                ]
            ],
        ],
    ]);
};