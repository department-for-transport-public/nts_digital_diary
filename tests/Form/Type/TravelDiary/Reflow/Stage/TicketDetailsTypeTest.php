<?php

namespace App\Tests\Form\Type\TravelDiary\Reflow\Stage;

use App\Entity\Journey\Stage;
use App\Form\TravelDiary\CombinedReflowWizard\Stage\TicketDetailsType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class TicketDetailsTypeTest extends AbstractKernelTypeTest
{
    private static array $validData = [
        'ticket' => ['ticketType' => 'Day return'],
        'ticketCost' => [
            'ticketCost' => ['hasCost' => 'true', 'cost' => '4.50'],
            'boardingCount' => '2',
        ],
    ];

    public function dataForm(): array
    {
        return [
            'Valid: complete data' => [self::$validData, true],
            'Valid: no ticket cost' => [
                [
                    'ticket' => ['ticketType' => 'Day return'],
                    'ticketCost' => [
                        'ticketCost' => ['hasCost' => 'false'],
                        'boardingCount' => '1',
                    ],
                ],
                true,
            ],

            // Ticket type
            'Invalid: missing ticket type' => [
                [
                    'ticket' => ['ticketType' => ''],
                    'ticketCost' => [
                        'ticketCost' => ['hasCost' => 'false'],
                        'boardingCount' => '1',
                    ],
                ],
                false,
            ],

            // Ticket cost
            'Invalid: ticket cost non-numeric' => [
                [
                    'ticket' => ['ticketType' => 'Day return'],
                    'ticketCost' => [
                        'ticketCost' => ['hasCost' => 'true', 'cost' => 'abc'],
                        'boardingCount' => '1',
                    ],
                ],
                false,
            ],
            'Invalid: ticket cost negative' => [
                [
                    'ticket' => ['ticketType' => 'Day return'],
                    'ticketCost' => [
                        'ticketCost' => ['hasCost' => 'true', 'cost' => '-5'],
                        'boardingCount' => '1',
                    ],
                ],
                false,
            ],

            // Boarding count
            'Invalid: boarding count zero' => [
                [
                    'ticket' => ['ticketType' => 'Day return'],
                    'ticketCost' => [
                        'ticketCost' => ['hasCost' => 'false'],
                        'boardingCount' => '0',
                    ],
                ],
                false,
            ],
            'Invalid: boarding count negative' => [
                [
                    'ticket' => ['ticketType' => 'Day return'],
                    'ticketCost' => [
                        'ticketCost' => ['hasCost' => 'false'],
                        'boardingCount' => '-1',
                    ],
                ],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $stage = new Stage();

        $form = $this->formFactory->create(TicketDetailsType::class, $stage, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
