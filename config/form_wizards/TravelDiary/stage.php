<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\TravelDiary\StageWizard\DetailsType;
use App\Form\TravelDiary\StageWizard\DriverAndParkingType;
use App\Form\TravelDiary\StageWizard\MethodType;
use App\Form\TravelDiary\StageWizard\TicketCostAndBoardingsType;
use App\Form\TravelDiary\StageWizard\TicketType;
use App\Form\TravelDiary\StageWizard\VehicleType;
use App\FormWizard\TravelDiary\StageState as State;

return static function (ContainerConfigurator $container) {
    $finishMetadata =  [
        'persist' => true,
        'redirect_route' => [
            'name' => 'traveldiary_journey_view',
            'parameters' => ['journeyId' => 'subject.journey.id'],
            'hash' => 'stage-{stage}',
            'hash_parameters' => ['{stage}' => 'subject.number']
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'form_wizard.travel_diary.stage' => [
                'type' => 'state_machine',
                'initial_marking' => State::STATE_METHOD,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [State::class],

                'places' => [
                    td_place(State::STATE_INTERMEDIARY, null, 'stage.intermediary', [
                        'is_valid_alternative_start_place' => 'true',
                        'translation_parameters' => [
                            'journeyStartLocation' => 'subject.journey.startLocationForDisplay',
                            'journeyEndLocation' => 'subject.journey.endLocationForDisplay',
                        ],
                    ], 'travel_diary/stage/intermediary.html.twig'),
                    td_place(State::STATE_METHOD, MethodType::class, 'stage.method', [
                        'is_valid_start_place' => 'isEmpty(state.getSubject().getId())',
                    ]),
                    td_place(State::STATE_DETAILS, DetailsType::class, 'stage.details', [
                        'is_valid_alternative_start_place' => '!isEmpty(state.getSubject().getId())',
                    ]),
                    td_place(State::STATE_VEHICLE, VehicleType::class, 'stage.vehicle', [
                        'is_valid_alternative_start_place' => '!isEmpty(state.getSubject().getId()) and state.getMethodType() === "private"',
                    ]),
                    td_place(State::STATE_DRIVER_AND_PARKING, DriverAndParkingType::class, 'stage.driver-and-parking'),
                    td_place(State::STATE_TICKET_TYPE, TicketType::class, 'stage.ticket', [
                        'is_valid_alternative_start_place' => '!isEmpty(state.getSubject().getId()) && state.getMethodType() === "public"',
                    ]),
                    td_place(State::STATE_TICKET_COST_AND_BOARDINGS, TicketCostAndBoardingsType::class, 'stage.ticket-cost-and-boardings'),
                    ['name' => State::STATE_FINISH],
                ],

                'transitions' => [
                    td_transition(
                        State::TRANSITION_INTERMEDIATY_TO_METHOD,
                        State::STATE_INTERMEDIARY,
                        State::STATE_METHOD
                    ),

                    td_transition(
                        State::TRANSITION_METHOD_TO_DETAILS,
                        State::STATE_METHOD,
                        State::STATE_DETAILS
                    ),
                    td_transition(
                        State::TRANSITION_DETAILS_TO_FINISH,
                        State::STATE_DETAILS,
                        State::STATE_FINISH,
                        'subject.getMethodType() === "other" or !isEmpty(subject.getSubject().getId())',
                        $finishMetadata
                    ),

                    td_transition(
                        State::TRANSITION_DETAILS_TO_VEHICLE,
                        State::STATE_DETAILS,
                        State::STATE_VEHICLE,
                        'subject.getMethodType() === "private" && isEmpty(subject.getSubject().getId())'
                    ),
                    td_transition(
                        State::TRANSITION_VEHICLE_TO_FINISH,
                        State::STATE_VEHICLE,
                        State::STATE_FINISH,
                        '!subject.isAdultDiary()',
                        $finishMetadata
                    ),

                    td_transition(
                        State::TRANSITION_VEHICLE_TO_DRIVER_AND_PARKING,
                        State::STATE_VEHICLE,
                        State::STATE_DRIVER_AND_PARKING,
                        'subject.isAdultDiary()'
                    ),
                    td_transition(
                        State::TRANSITION_DRIVER_AND_PARKING_TO_FINISH,
                        State::STATE_DRIVER_AND_PARKING,
                        State::STATE_FINISH,
                        null,
                        $finishMetadata
                    ),

                    td_transition(
                        State::TRANSITION_DETAILS_TO_TICKET_TYPE,
                        State::STATE_DETAILS,
                        State::STATE_TICKET_TYPE,
                        'subject.getMethodType() === "public" && isEmpty(subject.getSubject().getId())'
                    ),
                    td_transition(
                        State::TRANSITION_TICKET_TYPE_TO_TICKET_COST,
                        State::STATE_TICKET_TYPE,
                        State::STATE_TICKET_COST_AND_BOARDINGS
                    ),
                    td_transition(
                        State::TRANSITION_TICKET_COST_TO_FINISH,
                        State::STATE_TICKET_COST_AND_BOARDINGS,
                        State::STATE_FINISH,
                        null,
                        $finishMetadata
                    ),
                ]
            ],
        ],
    ]);
};