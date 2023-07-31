<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\InterviewerTrainingRecord as State;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'interviewer_training_state' => [
                'type' => 'state_machine',
                'audit_trail' => [
                    'enabled' => true,
                ],
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [
                    State::class,
                ],
                'initial_marking' => State::STATE_NEW,
                'places' => [
                    State::STATE_NEW,
                    State::STATE_IN_PROGRESS,
                    State::STATE_COMPLETE,
                ],
                'transitions' => [
                    State::TRANSITION_START => [
                        'from' => State::STATE_NEW,
                        'to' => State::STATE_IN_PROGRESS,
                    ],
                    State::TRANSITION_COMPLETE => [
                        'from' => State::STATE_IN_PROGRESS,
                        'to' => State::STATE_COMPLETE,
                    ],
                ]
            ]
        ],
    ]);
};