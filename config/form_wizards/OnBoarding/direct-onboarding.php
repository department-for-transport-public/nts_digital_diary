<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\OnBoarding\DiaryKeeperWizard\UserIdentifierType;
use App\Form\OnBoarding\DirectOnboardingWizard\DiaryKeeperLoginsType;
use App\Form\OnBoarding\DirectOnboardingWizard\ReviewHouseholdType;
use App\FormWizard\OnBoarding\DirectOnboardingState as State;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'workflows' => [
            'form_wizard.on_boarding.direct_onboarding' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    ob_place(State::STATE_INTRODUCTION, null, 'direct-onboarding.introduction', 'on_boarding/direct_onboarding/introduction.html.twig'),
                    ob_place(State::STATE_DIARY_KEEPER_LOGINS, DiaryKeeperLoginsType::class, 'direct-onboarding.diary-keeper-logins', 'on_boarding/base_form_with_help.html.twig', [
                        'view_data' => ['help_as_markdown' => true],
                    ]),
                    ob_place(State::STATE_DIARY_KEEPER_IDENTITY, UserIdentifierType::class, 'direct-onboarding.diary-keeper-identity', null, [
                        'form_data_property' => 'contextDiaryKeeper',
                        'translation_parameters' => ['name' => 'contextDiaryKeeper.name']
                    ]),
                    ob_place(State::STATE_REVIEW, ReviewHouseholdType::class, 'direct-onboarding.review', 'on_boarding/submit/index.html.twig'),
                    ob_place(State::STATE_FINISH, null, 'direct-onboarding.finish'),

                ],

                'transitions' => [
                    ob_transition(
                        State::TRANSITION_INTRO_TO_DK_MEDIA,
                        State::STATE_INTRODUCTION,
                        State::STATE_DIARY_KEEPER_LOGINS,
                    ),
                    ob_transition(
                        State::TRANSITION_DK_LOGINS_TO_DK_IDENTITY,
                        State::STATE_DIARY_KEEPER_LOGINS,
                        State::STATE_DIARY_KEEPER_IDENTITY,
                        null,
                        [
                            'transition_context' => ['diaryKeeperNumber' => 'nextDiaryKeeperNumber'],
                            'persist' => true
                        ],

                    ),
                    ob_transition(
                        State::TRANSITION_DK_IDENTITY_TO_NEXT_DK_IDENTITY,
                        State::STATE_DIARY_KEEPER_IDENTITY,
                        State::STATE_DIARY_KEEPER_IDENTITY,
                        'subject.getNextDiaryKeeperNumber()',
                        [
                            'transition_context' => ['diaryKeeperNumber' => 'nextDiaryKeeperNumber'],
                            'persist' => true,
                        ],
                    ),

                    ob_transition(
                        State::TRANSITION_DK_IDENTITY_TO_REVIEW,
                        State::STATE_DIARY_KEEPER_IDENTITY,
                        State::STATE_REVIEW,
                        'isNull(subject.getNextDiaryKeeperNumber())',
                        [
                            'persist' => true,
                        ],
                    ),

                    ob_transition(
                        State::TRANSITION_REVIEW_TO_FINISH,
                        State::STATE_REVIEW,
                        State::STATE_FINISH,
                        null,
                        [
                            'persist' => true,
                            'redirect_route' => 'onboarding_dashboard',
                            'submit_label' => 'direct-onboarding.review.submit-button',
                            'submit_label_translation_domain' => 'on-boarding',
                        ],
                    ),
                ]
            ],
        ],
    ]);
};