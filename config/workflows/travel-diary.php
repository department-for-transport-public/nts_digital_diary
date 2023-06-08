<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\DiaryKeeper;
use App\Entity\Household;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'travel_diary_state' => [
                'type' => 'state_machine',
                'audit_trail' => [
                    'enabled' => true,
                ],
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'diaryState',
                ],
                'supports' => [
                    DiaryKeeper::class,
                ],
                'initial_marking' => DiaryKeeper::STATE_NEW,
                'places' => [
                    DiaryKeeper::STATE_NEW,
                    DiaryKeeper::STATE_IN_PROGRESS,
                    DiaryKeeper::STATE_COMPLETED,
                    DiaryKeeper::STATE_APPROVED,
                    DiaryKeeper::STATE_DISCARDED,
                ],
                'transitions' => [
                    [
                        'from' => DiaryKeeper::STATE_NEW,
                        'to' => DiaryKeeper::STATE_IN_PROGRESS,
                        'name' => DiaryKeeper::TRANSITION_START,
                    ],
                    [
                        'from' => DiaryKeeper::STATE_IN_PROGRESS,
                        'to' => DiaryKeeper::STATE_COMPLETED,
                        'name' => DiaryKeeper::TRANSITION_COMPLETE,
                    ],
                    [
                        'from' => DiaryKeeper::STATE_COMPLETED,
                        'to' => DiaryKeeper::STATE_IN_PROGRESS,
                        'name' => DiaryKeeper::TRANSITION_UNDO_COMPLETE,
                    ],
                    [
                        'from' => [DiaryKeeper::STATE_NEW, DiaryKeeper::STATE_IN_PROGRESS, DiaryKeeper::STATE_COMPLETED],
                        'to' => DiaryKeeper::STATE_APPROVED,
                        'name' => DiaryKeeper::TRANSITION_APPROVE,
                    ],
                    [
                        'from' => DiaryKeeper::STATE_APPROVED,
                        'to' => DiaryKeeper::STATE_COMPLETED,
                        'name' => DiaryKeeper::TRANSITION_UNDO_APPROVAL,
                        'guard' => sprintf("subject.getHousehold().getState() !== '%s'", Household::STATE_SUBMITTED),
                    ],
                    [
                        'from' => [DiaryKeeper::STATE_NEW, DiaryKeeper::STATE_IN_PROGRESS, DiaryKeeper::STATE_COMPLETED],
                        'to' => DiaryKeeper::STATE_DISCARDED,
                        'name' => DiaryKeeper::TRANSITION_DISCARD
                    ],
                    [
                        'from' => DiaryKeeper::STATE_DISCARDED,
                        'to' => DiaryKeeper::STATE_COMPLETED,
                        'name' => DiaryKeeper::TRANSITION_UNDO_DISCARD,
                        'guard' => sprintf("subject.getHousehold().getState() !== '%s'", Household::STATE_SUBMITTED),
                    ],
                ]
            ]
        ],
    ]);
};