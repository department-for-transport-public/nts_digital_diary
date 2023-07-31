<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\TravelDiary\SplitJourneyWizard\MidpointType;
use App\Form\TravelDiary\SplitJourneyWizard\PurposeType;
use App\FormWizard\TravelDiary\SplitJourneyState as State;

return static function (ContainerConfigurator $container) {
    $editFinishMetadata =  [
        'persist' => true,
        'redirect_route' => [
            'name' => 'traveldiary_dashboard_day',
            'parameters' => ['dayNumber' => 'subject.sourceJourney.diaryDay.number'],
        ],
    ];

    $introductionTemplate = 'travel_diary/split_journey/wizard_introduction.html.twig';
    $defaultTemplate = 'travel_diary/split_journey/wizard_form.html.twig';

    $container->extension('framework', [
        'workflows' => [
            'form_wizard.travel_diary.split_journey' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    td_place(State::STATE_INTRODUCTION,null, 'split-journey.introduction', [], $introductionTemplate),
                    td_place(State::STATE_MIDPOINT,MidpointType::class, 'split-journey.midpoint', [], $defaultTemplate),
                    td_place(State::STATE_PURPOSE,PurposeType::class, 'journey.purpose', [], $defaultTemplate),
                    ['name' => State::STATE_FINISH],
                ],

                'transitions' => [
                    td_transition(
                        State::TRANSITION_INTRO_TO_MIDPOINT,
                        State::STATE_INTRODUCTION,
                        State::STATE_MIDPOINT,
                    ),
                    td_transition(
                        State::TRANSITION_MIDPOINT_TO_PURPOSE,
                        State::STATE_MIDPOINT,
                        State::STATE_PURPOSE,
                        '!subject.getSubject().isDestinationToHome()',
                    ),
                    td_transition(
                        State::TRANSITION_MIDPOINT_TO_FINISH,
                        State::STATE_MIDPOINT,
                        State::STATE_FINISH,
                        'subject.getSubject().isDestinationToHome()',
                        $editFinishMetadata
                    ),
                    td_transition(
                        State::TRANSITION_PURPOSE_TO_FINISH,
                        State::STATE_PURPOSE,
                        State::STATE_FINISH,
                        null,
                        $editFinishMetadata
                    ),
                ]
            ],
        ],
    ]);
};