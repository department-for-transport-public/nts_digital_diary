<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\TravelDiary\JourneyWizard\PurposeType;
use App\Form\TravelDiary\ReturnJourneyWizard\TimesType;
use App\Form\TravelDiary\ReturnJourneyWizard\StageDetailsType;
use App\Form\TravelDiary\ReturnJourneyWizard\TargetDayType;
use App\FormWizard\TravelDiary\ReturnJourneyState as State;

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
            'form_wizard.travel_diary.return_journey' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    td_place(State::STATE_INTRODUCTION,null, 'return-journey.introduction', [
                        'translation_parameters' => [
                            'dayNumber' => 'targetDayNumber',
                            'startLocation' => 'subject.startLocationForDisplay',
                            'endLocation' => 'subject.endLocationForDisplay',
                        ],
                    ], 'travel_diary/return_journey/wizard_introduction.html.twig'),
                    td_place(State::STATE_PURPOSE,PurposeType::class, 'journey.purpose'),
                    td_place(State::STATE_DAY,TargetDayType::class, 'return-journey.select-day'),
                    td_place(State::STATE_TIMES,TimesType::class, 'return-journey.journey-times'),
                    td_place(State::STATE_STAGE_DETAILS,StageDetailsType::class, 'return-journey.stage-details', [
                        'form_data_property' => 'contextStage',
                        'view_data' => [
                            'title_translatable_message' => 'stageDetailsTitle',
                        ],
                    ]),

                    ['name' => State::STATE_FINISH],
                ],

                'transitions' => [
                    td_transition(
                        State::TRANSITION_INTRO_TO_DAY,
                        State::STATE_INTRODUCTION,
                        State::STATE_DAY,
                    ),
                    td_transition(
                        State::TRANSITION_DAY_TO_TIMES,
                        State::STATE_DAY,
                        State::STATE_TIMES,
                        'subject.getSubject().isGoingHome()'
                    ),

                    td_transition(
                        State::TRANSITION_DAY_TO_PURPOSE,
                        State::STATE_DAY,
                        State::STATE_PURPOSE,
                        '!subject.getSubject().isGoingHome()'
                    ),
                    td_transition(
                        State::TRANSITION_PURPOSE_TO_TIMES,
                        State::STATE_PURPOSE,
                        State::STATE_TIMES,
                    ),
                    td_transition(
                        State::TRANSITION_TIMES_TO_FINISH,
                        State::STATE_TIMES,
                        State::STATE_FINISH,
                        'subject.getStageCount() == 0',
                        $editFinishMetadata
                    ),
                    td_transition(
                        State::TRANSITION_TIMES_TO_STAGE,
                        State::STATE_TIMES,
                        State::STATE_STAGE_DETAILS,
                        'subject.getStageCount() > 0',
                        ['context' => [
                            'stageNumber' => 1
                        ]]
                    ),
                    td_transition(
                        State::TRANSITION_STAGE_TO_NEXT_STAGE,
                        State::STATE_STAGE_DETAILS,
                        State::STATE_STAGE_DETAILS,
                        'subject.getStageCount() > subject.getStageNumber()',
                        ['context' => [
                            'stageNumber' => 'nextStageNumber'
                        ]]
                    ),
                    td_transition(
                        State::TRANSITION_STAGE_TO_FINISH,
                        State::STATE_STAGE_DETAILS,
                        State::STATE_FINISH,
                        'subject.getStageCount() <= subject.getStageNumber()',
                        $editFinishMetadata
                    ),
                ]
            ],
        ],
    ]);
};