<?php

namespace App\Tests\Form\Type\Onboarding\DiaryKeeperWizard;

use App\Entity\DiaryKeeper;
use App\Form\OnBoarding\DirectOnboardingWizard\DiaryKeeperLoginsType;
use App\Tests\DataFixtures\TestSpecific\Onboarding\DiaryKeeperLoginsTestFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use Doctrine\Common\DataFixtures\ReferenceRepository;

class DiaryKeeperLoginsTypeTest extends AbstractKernelTypeTest
{
    protected ReferenceRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $exec = $this->databaseTool->loadFixtures([
            DiaryKeeperLoginsTestFixtures::class,
        ]);

        $this->repository = $exec->getReferenceRepository();
    }

    public function dataForm(): array
    {
        return [
            'Valid: Not submitting anything' => [
                [],
                true
            ],
            'Valid: Using an email' => [
                [
                    'diaryKeepers' => [
                        0 => [
                            'user' => ['username' => 'test@example.com'],
                        ],
                    ],
                ],
                true
            ],
            'Valid: Using an email that is already in use by a training user' => [
                [
                    'diaryKeepers' => [
                        0 => [
                            'user' => ['username' => 'winnie@example.com'],
                        ],
                    ],
                ],
                true
            ],
            'Invalid: Entering the same email for two diary keepers' => [
                [
                    'diaryKeepers' => [
                        0 => [
                            'user' => ['username' => 'test@example.com'],
                        ],
                        1 => [
                            'user' => ['username' => 'test@example.com'],
                        ],
                    ],
                ],
                false
            ],
            'Invalid: Using an email that is already in use by a diary keeper in another house' => [
                [
                    'diaryKeepers' => [
                        0 => [
                            'user' => ['username' => 'kosh@example.com'],
                        ],
                    ],
                ],
                false
            ],
            'Invalid: Using an email that is already in use by an interviewer' => [
                [
                    'diaryKeepers' => [
                        0 => [
                            'user' => ['username' => 'poppy@example.com'],
                        ],
                    ],
                ],
                false
            ],
            'Invalid: Setting a stupidly-long email' => [
                [
                    'diaryKeepers' => [
                        0 => [
                            'user' => [
                                'username' => '012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789@example.com'
                            ],
                        ],
                    ],
                ],
                false
            ],
            'Invalid: Setting a nonsense email' => [
                [
                    'diaryKeepers' => [
                        0 => [
                            'user' => ['username' => 'banana-apple'],
                        ],
                    ],
                ],
                false
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedToBeValid): void
    {
        $diaryKeeperOne = $this->repository->getReference('diary-keeper:111984/01/1/1', DiaryKeeper::class);
        $data = [
            'diaryKeepers' => [$diaryKeeperOne]
        ];

        $form = $this->formFactory->create(DiaryKeeperLoginsType::class, $data, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}
