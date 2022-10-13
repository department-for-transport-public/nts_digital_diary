<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\TravelDiary\ShareJourneyWizard\PurposesType;
use App\Form\TravelDiary\ShareJourneyWizard\ShareToType;
use App\Form\TravelDiary\ShareJourneyWizard\StageDetailsType;
use App\FormWizard\TravelDiary\ShareJourneyState as State;

/**

- intro
- who (list other members of household)
for each person
  - journey purpose

  for each private stage
    - parking cost
    - driver/passenger
  for each public stage
    - ticket type
    - ticket cost

 */

return static function (ContainerConfigurator $container) {
    $finishMetadata = [
        'redirect_route' => [
            'name' => 'traveldiary_journey_view',
            'parameters' => ['journeyId' => 'subject.id'],
        ],
    ];

    $editFinishMetadata = array_merge($finishMetadata, [
        'persist' => true,
    ]);

    $container->extension('framework', [
        'workflows' => [
            'form_wizard.travel_diary.share_journey' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_INTRO,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    td_place(State::STATE_INTRO, null, 'share-journey.introduction', [], 'travel_diary/share_journey/wizard_introduction.html.twig'),
                    td_place(State::STATE_WHO_WITH, ShareToType::class, 'share-journey.who-with', [], 'travel_diary/share_journey/base_form.html.twig'),
                    td_place(State::STATE_PURPOSES, PurposesType::class, 'share-journey.purposes', [], 'travel_diary/share_journey/purposes.html.twig'),
                    td_place(State::STATE_STAGE_DETAILS, StageDetailsType::class, 'share-journey.stage-details', [
                        'form_data_property' => 'contextStage',
                    ], 'travel_diary/share_journey/stage_details.html.twig'),
                    ['name' => State::STATE_FINISH],
                ],

                'transitions' => [
                    td_transition(State::TRANSITION_INTRO_TO_WHO_WITH, State::STATE_INTRO, State::STATE_WHO_WITH,
                        'is_granted("CAN_SHARE_JOURNEY", subject.getSubject())'
                    ),

                    // For when there are no available recipients...
                    td_transition(State::TRANSITION_INTRO_TO_FINISH, State::STATE_INTRO, State::STATE_FINISH,
                        '!is_granted("CAN_SHARE_JOURNEY", subject.getSubject())',
                        array_merge($finishMetadata, [
                            'submit_label' => 'share-journey.cannot-share.submit.label',
                            'submit_translation_domain' => 'travel-diary',
                        ])
                    ),

                    td_transition(State::TRANSITION_WHO_WITH_TO_PURPOSE, State::STATE_WHO_WITH, State::STATE_PURPOSES),
                    td_transition(State::TRANSITION_PURPOSE_TO_FINISH, State::STATE_PURPOSES,
                        State::STATE_FINISH,'!subject.hasNextStage()',
                        $editFinishMetadata
                    ),
                    td_transition(State::TRANSITION_PURPOSE_TO_STAGE_DETAILS, State::STATE_PURPOSES,
                        State::STATE_STAGE_DETAILS, 'subject.hasNextStage()', [
                            'context' => [
                                'stageNumber' => 'nextStageNumber'
                            ]
                        ]),
                    td_transition(State::TRANSITION_STAGE_TO_NEXT_STAGE,State::STATE_STAGE_DETAILS,
                        State::STATE_STAGE_DETAILS,'subject.hasNextStage()', [
                            'context' => [
                                'stageNumber' => 'nextStageNumber'
                            ]
                        ]),
                    td_transition(State::TRANSITION_STAGE_DETAILS_TO_FINISH, State::STATE_STAGE_DETAILS,
                        State::STATE_FINISH,'!subject.hasNextStage()',
                        $editFinishMetadata
                    ),
                ],
            ]
        ]
    ]);
};