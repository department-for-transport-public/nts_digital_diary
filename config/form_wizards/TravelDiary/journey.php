<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\TravelDiary\JourneyWizard\LocationsType;
use App\Form\TravelDiary\JourneyWizard\PurposeType;
use App\Form\TravelDiary\JourneyWizard\TimesType;
use App\FormWizard\TravelDiary\JourneyState;
use App\FormWizard\TravelDiary\StageState;

return static function (ContainerConfigurator $container) {
    $addFinishMetadata =  [
        'persist' => true,
        'redirect_route' => [
            'name' => 'traveldiary_stage_wizard_place',
            'parameters' => [
                'journeyId' => 'subject.id',
                'place' => StageState::STATE_INTERMEDIARY,
            ],
        ],
    ];
    $editFinishMetadata =  [
        'persist' => true,
        'redirect_route' => [
            'name' => 'traveldiary_journey_view',
            'parameters' => ['journeyId' => 'subject.id'],
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'form_wizard.travel_diary.journey' => [
                'type' => 'state_machine',
                'initial_marking' => JourneyState::STATE_LOCATIONS,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [JourneyState::class],

                'places' => [
                    td_place(JourneyState::STATE_LOCATIONS, LocationsType::class, 'journey.locations'),
                    td_place(JourneyState::STATE_TIMES, TimesType::class, 'journey.times'),
                    td_place(JourneyState::STATE_PURPOSE,PurposeType::class, 'journey.purpose', [
                        'is_valid_alternative_start_place' => '!isEmpty(state.getSubject().getId())'
                    ]),
                    ['name' => JourneyState::STATE_FINISH],
                ],

                'transitions' => [
                    td_transition(
                        JourneyState::TRANSITION_LOCATIONS_TO_TIMES,
                        JourneyState::STATE_LOCATIONS,
                        JourneyState::STATE_TIMES,
                    ),
                    td_transition(
                        JourneyState::TRANSITION_TIMES_TO_FINISH . '-edit',
                        JourneyState::STATE_TIMES,
                        JourneyState::STATE_FINISH,
                        '!isEmpty(subject.getSubject().getId())',
                        $editFinishMetadata
                    ),
                    td_transition(
                        JourneyState::TRANSITION_TIMES_TO_FINISH,
                        JourneyState::STATE_TIMES,
                        JourneyState::STATE_FINISH,
                        'isEmpty(subject.getSubject().getId()) and subject.getSubject().isGoingHome()',
                        $addFinishMetadata
                    ),
                    td_transition(
                        JourneyState::TRANSITION_TIMES_TO_PURPOSE,
                        JourneyState::STATE_TIMES,
                        JourneyState::STATE_PURPOSE,
                        'isEmpty(subject.getSubject().getId()) and !subject.getSubject().isGoingHome()'
                    ),
                    td_transition(
                        JourneyState::TRANSITION_PURPOSE_TO_FINISH,
                        JourneyState::STATE_PURPOSE,
                        JourneyState::STATE_FINISH,
                        'isEmpty(subject.getSubject().getId())',
                        $addFinishMetadata
                    ),
                    td_transition(
                        JourneyState::TRANSITION_PURPOSE_TO_FINISH . '-edit',
                        JourneyState::STATE_PURPOSE,
                        JourneyState::STATE_FINISH,
                        '!isEmpty(subject.getSubject().getId())',
                        $editFinishMetadata
                    ),
                ]
            ],
        ],
    ]);
};