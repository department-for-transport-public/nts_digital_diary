<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\OnBoarding\HouseholdWizard\HouseholdType;
use App\FormWizard\OnBoarding\HouseholdState as State;

return static function (ContainerConfigurator $container) {
    $editFinishMetadata =  [
        'persist' => true,
        'redirect_route' => 'onboarding_dashboard',
    ];

    $container->extension('framework', [
        'workflows' => [
            'form_wizard.on_boarding.household' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    ob_place(State::STATE_INTRODUCTION, null, 'household.introduction', 'on_boarding/household/introduction.html.twig'),
                    ob_place(State::STATE_DETAILS, HouseholdType::class, 'household.details', 'on_boarding/base_form_with_help.html.twig', [
                        'is_valid_alternative_start_place' => '!isEmpty(state.getSubject().getId())'
                    ]),
                    ['name' => State::STATE_FINISH],
                ],

                'transitions' => [
                    ob_transition(
                        State::TRANSITION_INTRODUCTION_TO_DETAILS,
                        State::STATE_INTRODUCTION,
                        State::STATE_DETAILS
                    ),
                    ob_transition(
                        State::TRANSITION_DETAILS_TO_FINISH,
                        State::STATE_DETAILS,
                        State::STATE_FINISH,
                        null,
                        $editFinishMetadata
                    ),
                ]
            ],
        ],
    ]);
};