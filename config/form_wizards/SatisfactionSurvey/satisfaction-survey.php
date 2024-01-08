<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\SatisfactionSurvey\BurdenOfUseType;
use App\Form\SatisfactionSurvey\DiaryCompletionType;
use App\Form\SatisfactionSurvey\EaseOfUseType;
use App\Form\SatisfactionSurvey\PreferredMethodType;
use App\Form\SatisfactionSurvey\TypeOfDevicesType;
use App\FormWizard\SatisfactionSurvey\SatisfactionSurveyState as State;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'form_wizard.satisfaction_survey' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_EASE_OF_USE,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    satis_place(State::STATE_EASE_OF_USE,'satisfaction_survey/ease_of_use.html.twig', EaseOfUseType::class),
                    satis_place(State::STATE_BURDEN_OF_USE,'satisfaction_survey/burden_of_use.html.twig',BurdenOfUseType::class),
                    satis_place(State::STATE_TYPE_OF_DEVICES,'satisfaction_survey/type_of_devices.html.twig',TypeOfDevicesType::class),
                    satis_place(State::STATE_DIARY_COMPLETION,'satisfaction_survey/diary_completion.html.twig',DiaryCompletionType::class),
                    satis_place(State::STATE_PREFERRED_METHOD,'satisfaction_survey/preferred_method.html.twig',PreferredMethodType::class),
                    ['name' => State::STATE_FINISH],
                ],

                'transitions' => [
                    satis_transition(
                        State::TRANSITION_EASE_OF_USE_TO_BURDEN,
                        State::STATE_EASE_OF_USE,
                        State::STATE_BURDEN_OF_USE,
                    ),
                    satis_transition(
                        State::TRANSITION_BURDEN_TO_TYPE_OF_DEVICES,
                        State::STATE_BURDEN_OF_USE,
                        State::STATE_TYPE_OF_DEVICES,
                    ),
                    satis_transition(
                        State::TRANSITION_TYPE_OF_DEVICES_TO_DIARY_COMPLETION,
                        State::STATE_TYPE_OF_DEVICES,
                        State::STATE_DIARY_COMPLETION,
                    ),
                    satis_transition(
                        State::TRANSITION_DIARY_COMPLETION_TO_PREFERRED_METHOD,
                        State::STATE_DIARY_COMPLETION,
                        State::STATE_PREFERRED_METHOD,
                    ),
                    satis_transition(
                        State::TRANSITION_PREFERRED_METHOD_TO_FINISH,
                        State::STATE_PREFERRED_METHOD,
                        State::STATE_FINISH,
                        null,
                        [
                            'persist' => true,
                            'redirect_route' => 'traveldiary_dashboard',
                        ]
                    ),
                ]
            ],
        ],
    ]);
};