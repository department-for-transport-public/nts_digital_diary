<?php

namespace App\Tests\Form\Type\Onboarding\DiaryKeeperWizard;

use App\Entity\AreaPeriod;
use App\Entity\Household;
use App\Form\OnBoarding\DirectOnboardingWizard\Ingress\HouseholdType;
use App\Tests\DataFixtures\AreaPeriodFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use App\Utility\TravelDiary\SerialHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;

class HouseholdTypeTest extends AbstractKernelTypeTest
{
    protected ReferenceRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $exec = $this->databaseTool->loadFixtures([
            AreaPeriodFixtures::class,
        ]);

        $this->repository = $exec->getReferenceRepository();
    }

    public function dataForm(): array
    {
        $validHouseholdData = [
            'add' => '1',
            'h' => '1',
            'cl' => SerialHelper::getCheckLetter('111984', '1', '1'),
            'start' => '2021-11-01', // Related to the AreaPeriod's date
            'dk' => [
                '1' => [
                    'name' => 'Apple',
                    'adult' => '1',
                ]
            ],
        ];

        $validHouseholdDataWithVehicles = [
            'add' => '1',
            'h' => '1',
            'cl' => SerialHelper::getCheckLetter('111984', '1', '1'),
            'start' => '2021-11-01', // Related to the AreaPeriod's date
            'dk' => [
                '1' => [
                    'name' => 'Apple',
                    'adult' => '1',
                ],
                '2' => [
                    'name' => 'Banana',
                    'adult' => '0',
                ]
            ],
            'v' => [
                '1' => [
                    'name' => 'Red Car',
                    'method' => '4',
                    'driver' => '1',
                ],
                '2' => [
                    'name' => 'Blue Bike',
                    'method' => '5',
                    'driver' => '2',
                ],
                '3' => [
                    'name' => 'Orange Van',
                    'method' => '6',
                    'driver' => '2',
                ],
            ],
        ];

        return [
            'Valid: Submitting a household with diary keeper' => [
                $validHouseholdData,
                true
            ],
            'Valid: Submitting a household with diary keepers and vehicles' => [
                $validHouseholdDataWithVehicles,
                true
            ],

            // Basics
            'Invalid: Submitting nothing' => [
                [],
                false
            ],
            'Invalid: Extra data' => [
                array_merge($validHouseholdData, [
                    'extra' => 'added-value',
                ]),
                false
            ],
            'Invalid: Submitting an incorrect check letter' => [
                array_merge($validHouseholdData, [
                    'cl' => SerialHelper::getCheckLetter('111984', '2', '2')
                ]),
                false
            ],
            'Invalid: Address number out of range (too small)' => [
                array_merge($validHouseholdData, [
                    'add' => '0',
                    'cl' => SerialHelper::getCheckLetter('111984', '0', '1')
                ]),
                false
            ],
            'Invalid: Address number out of range (too large)' => [
                array_merge($validHouseholdData, [
                    'add' => '100',
                    'cl' => SerialHelper::getCheckLetter('111984', '100', '1')
                ]),
                false
            ],
            'Invalid: Household number out of range (too small)' => [
                array_merge($validHouseholdData, [
                    'h' => '0',
                    'cl' => SerialHelper::getCheckLetter('111984', '1', '0')
                ]),
                false
            ],
            'Invalid: Household number out of range (too large)' => [
                array_merge($validHouseholdData, [
                    'h' => '100',
                    'cl' => SerialHelper::getCheckLetter('111984', '1', '100')
                ]),
                false
            ],
            'Invalid: Start date out of range (too early)' => [
                array_merge($validHouseholdData, [
                    'start' => '2021-10-30',
                ]),
                false
            ],
            'Invalid: Start date out of range (too late)' => [
                array_merge($validHouseholdData, [
                    'start' => '2022-02-01',
                ]),
                false
            ],

            // Diary-keeper
            'Invalid: Diary-keeper with nonsense key' => [
                array_merge($validHouseholdData, [
                    'dk' => [
                        'banana' => [
                            'name' => 'Apple',
                            'adult' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Diary-keeper with out of range key (too small)' => [
                array_merge($validHouseholdData, [
                    'dk' => [
                        '0' => [
                            'name' => 'Apple',
                            'adult' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Diary-keeper with out of range key (too large)' => [
                array_merge($validHouseholdData, [
                    'dk' => [
                        '100' => [
                            'name' => 'Apple',
                            'adult' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Diary-keeper with empty adult value' => [
                array_merge($validHouseholdData, [
                    'dk' => [
                        '1' => [
                            'name' => 'Apple',
                            'adult' => '',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Diary-keeper with invalid adult value' => [
                array_merge($validHouseholdData, [
                    'dk' => [
                        '1' => [
                            'name' => 'Apple',
                            'adult' => '2',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Diary-keeper with nonsense adult value' => [
                array_merge($validHouseholdData, [
                    'dk' => [
                        '1' => [
                            'name' => 'Apple',
                            'adult' => 'banana',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Diary-keeper with empty name' => [
                array_merge($validHouseholdData, [
                    'dk' => [
                        '1' => [
                            'name' => '',
                            'adult' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Diary-keeper with a stupidly long name' => [
                array_merge($validHouseholdData, [
                    'dk' => [
                        '1' => [
                            'name' => str_repeat('B', 256),
                            'adult' => '1',
                        ]
                    ],
                ]),
                false
            ],

            // Vehicles
            'Invalid: Vehicle with an empty driver' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '1' => [
                            'name' => 'Red Golf',
                            'method' => '4',
                            'driver' => '',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with an invalid driver' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '1' => [
                            'name' => 'Red Golf',
                            'method' => '4',
                            'driver' => '0', // Should be either 1 or 2, to match DK keys
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with a nonsense driver' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '1' => [
                            'name' => 'Red Golf',
                            'method' => '4',
                            'driver' => 'banana',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with an empty method' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '1' => [
                            'name' => 'Red Golf',
                            'method' => '',
                            'driver' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with an invalid method' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '1' => [
                            'name' => 'Red Golf',
                            'method' => '2', // Valid values are 4,5,6
                            'driver' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with a nonsense method' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '1' => [
                            'name' => 'Red Golf',
                            'method' => 'flamingo',
                            'driver' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with an empty name' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '1' => [
                            'name' => '',
                            'method' => '4',
                            'driver' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with a stupidly long name' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '1' => [
                            'name' => str_repeat('A', 256),
                            'method' => '4',
                            'driver' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with an out of range key (too low)' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '0' => [
                            'name' => 'Red Car',
                            'method' => '4',
                            'driver' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with an out of range key (too high)' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        '100' => [
                            'name' => 'Red Car',
                            'method' => '4',
                            'driver' => '1',
                        ]
                    ],
                ]),
                false
            ],
            'Invalid: Vehicle with a nonsense key' => [
                array_merge($validHouseholdData, [
                    'v' => [
                        'banana' => [
                            'name' => 'Red Car',
                            'method' => '4',
                            'driver' => '1',
                        ]
                    ],
                ]),
                false
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedToBeValid): void
    {
        $areaPeriod = $this->repository->getReference('area-period:1', AreaPeriod::class);
        $data = (new Household())->setAreaPeriod($areaPeriod);

        $form = $this->formFactory->create(HouseholdType::class, $data, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}
