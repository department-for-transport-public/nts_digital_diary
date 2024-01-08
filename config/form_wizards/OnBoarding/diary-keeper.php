<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\OnBoarding\DiaryKeeperWizard\AddAnotherType;
use App\Form\OnBoarding\DiaryKeeperWizard\DetailsType;
use App\Form\OnBoarding\DiaryKeeperWizard\UserIdentifierType;
use App\FormWizard\OnBoarding\DiaryKeeperState as State;

return static function (ContainerConfigurator $container) {
    $editFinishMetadata =  [
        'persist' => true,
        'redirect_route' => 'onboarding_dashboard',
    ];

    $dkAddedBanner = [
        'title' => 'diary-keeper.added.notification-banner.title',
        'heading' => 'diary-keeper.added.notification-banner.heading',
        'content' => 'diary-keeper.added.notification-banner.content',
        'translation_domain' => 'on-boarding',
        'translation_parameters' => [
            'number' => 'subject.number',
            'name' => 'subject.name',
        ],
    ];

    $maintenanceFinishMetadata =  [
        'persist' => true,
        'redirect_route' => [
            'name' => 'admin_household_maintenance_details',
            'parameters' => [
                'id' => 'subject.household.id',
            ],
        ],
        'notification_banner' => $dkAddedBanner,
    ];

    $container->extension('framework', [
        'workflows' => [
            'form_wizard.on_boarding.diary_keeper' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_DETAILS,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    ob_place(State::STATE_DETAILS, DetailsType::class, 'diary-keeper.details'),
                    ob_place(State::STATE_IDENTITY, UserIdentifierType::class, 'diary-keeper.user-identifier', "on_boarding/base_form_with_help.html.twig", [
                        'is_valid_alternative_start_place' => '!isEmpty(state.getSubject().getId())',
                    ]),
                    ob_place(State::STATE_ADD_ANOTHER, AddAnotherType::class, 'diary-keeper.add-another', null, [
                        'clear_state' => true,
                        'is_valid_alternative_start_place' => true,
                        'form_data_property' => false,
                        'view_data' => ['show_formwizard_backlink' => false]
                    ]),
                    ['name' => State::STATE_FINISH],
                ],

                'transitions' => [
                    ob_transition(
                        State::TRANSITION_DETAILS_TO_IDENTITY,
                        State::STATE_DETAILS,
                        State::STATE_IDENTITY,
                        'isEmpty(subject.getSubject().getId())',
                    ),
                    ob_transition(
                        State::TRANSITION_DETAILS_TO_FINISH,
                        State::STATE_DETAILS,
                        State::STATE_FINISH,
                        '!isEmpty(subject.getSubject().getId())',
                        $editFinishMetadata
                    ),

                    ob_transition(
                        State::TRANSITION_IDENTITY_TO_FINISH,
                        State::STATE_IDENTITY,
                        State::STATE_FINISH,
                        '!subject.isAddAnotherDisabled() && !isEmpty(subject.getSubject().getId())',
                        $editFinishMetadata
                    ),
                    // See comment in DiaryKeeperState.php
                    ob_transition(
                        State::TRANSITION_IDENTITY_TO_FINISH_HOUSEHOLD_MAINTENANCE,
                        State::STATE_IDENTITY,
                        State::STATE_FINISH,
                        'subject.isAddAnotherDisabled()',
                        $maintenanceFinishMetadata
                    ),
                    ob_transition(
                        State::TRANSITION_IDENTITY_TO_ADD_ANOTHER,
                        State::STATE_IDENTITY,
                        State::STATE_ADD_ANOTHER,
                        'isEmpty(subject.getSubject().getId()) && !subject.isAddAnotherDisabled()',
                        [
                            'notification_banner' => $dkAddedBanner,
                            'persist' => true,
                        ]
                    ),
                    ob_transition(
                        State::TRANSITION_ADD_ANOTHER,
                        State::STATE_ADD_ANOTHER,
                        State::STATE_DETAILS,
                        "isFormDataPropertySameAs('[add_another]', true)",
                        [
                            'redirect_route' => 'onboarding_diarykeeper_add',
                        ]
                    ),
                    ob_transition(
                        State::TRANSITION_FINISH,
                        State::STATE_ADD_ANOTHER,
                        State::STATE_FINISH,
                        "!isFormDataPropertySameAs('[add_another]', true)",
                        [
                            'redirect_route' => 'onboarding_dashboard',
                        ]
                    ),
                ]
            ],
        ],
    ]);
};