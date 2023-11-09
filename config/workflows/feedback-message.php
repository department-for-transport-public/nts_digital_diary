<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\Feedback\Message as State;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'feedback_message' => [
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
                    State::STATE_ASSIGNED,
                    State::STATE_CLOSED,
                ],
                'transitions' => [
                    State::TRANSITION_ASSIGN => [
                        'from' => [State::STATE_NEW, State::STATE_ASSIGNED, State::STATE_IN_PROGRESS],
                        'to' => State::STATE_ASSIGNED,
                    ],
                    State::TRANSITION_ACKNOWLEDGE => [
                        'from' => State::STATE_ASSIGNED,
                        'to' => State::STATE_IN_PROGRESS,
                    ],
                    State::TRANSITION_CLOSE => [
                        'from' => State::STATE_IN_PROGRESS,
                        'to' => State::STATE_CLOSED,
                    ],
                ]
            ]
        ],
    ]);
};