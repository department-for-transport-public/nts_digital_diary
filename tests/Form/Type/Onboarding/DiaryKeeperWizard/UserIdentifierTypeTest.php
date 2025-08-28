<?php

namespace App\Tests\Form\Type\Onboarding\DiaryKeeperWizard;

use App\Entity\DiaryKeeper;
use App\Form\OnBoarding\DiaryKeeperWizard\UserIdentifierType;
use App\Tests\DataFixtures\TestSpecific\Onboarding\UserIdentityTypeTestFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use Doctrine\Common\DataFixtures\ReferenceRepository;

class UserIdentifierTypeTest extends AbstractKernelTypeTest
{
    protected ReferenceRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $exec = $this->databaseTool->loadFixtures([
            UserIdentityTypeTestFixtures::class,
        ]);

        $this->repository = $exec->getReferenceRepository();
    }

    public function dataForm(): array
    {
        return [
            'Valid: Using an email' => [
                [
                    'mediaType' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user' => [
                        'username' => 'mark@example.com',
                        'proxies' => [],
                    ],
                ],
                true
            ],
            'Valid: Choosing a proxy' => [
                [
                    'mediaType' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user' => [
                        'username' => '',
                        'proxies' => ['diary-keeper:111984/01/1/2'],
                    ],
                ],
                true
            ],
            'Valid: Setting the media type to "paper"' => [
                [
                    'mediaType' => 'other',
                    'interviewerMediaType' => DiaryKeeper::MEDIA_TYPE_PAPER,
                ],
                true
            ],
            'Invalid: Using an email that is already in use' => [
                [
                    'mediaType' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user' => [
                        'username' => 'linda@example.com',
                        'proxies' => [],
                    ],
                ],
                false
            ],
            'Invalid: Choosing an invalid proxy' => [
                [
                    'mediaType' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user' => [
                        'username' => '',
                        // Poppy doesn't have an email set, and as such is not a valid choice as a proxy
                        // (is in the list, but is disabled with an explanation)
                        'proxies' => ['diary-keeper:111984/01/1/3'],
                    ],
                ],
                false
            ],
            'Invalid: Setting a stupidly-long email' => [
                [
                    'mediaType' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user' => [
                        'username' => '012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789@example.com',
                        'proxies' => [],
                    ],
                ],
                false
            ],
            'Invalid: Setting the mediaType to nonsense' => [
                [
                    'mediaType' => 'banana',
                ],
                false
            ],
            'Invalid: Setting a nonsense email' => [
                [
                    'mediaType' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user' => [
                        'username' => 'banana-apple',
                        'proxies' => [],
                    ],
                ],
                false
            ],
            'Invalid: Choosing a nonsense proxy' => [
                [
                    'mediaType' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                    'user' => [
                        'username' => '',
                        'proxies' => ['carrot'],
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
        // Try to turn the proxy references into DiaryKeeper IDs (as are used in the form)
        if (isset($formData['user']['proxies'])) {
            foreach($formData['user']['proxies'] as $i => $proxyId) {
                try {
                    $formData['user']['proxies'][$i] = $this->repository->getReference($proxyId, DiaryKeeper::class)->getId();
                }
                catch(\OutOfBoundsException) {}
            }
        }

        $diaryKeeperOne = $this->repository->getReference('diary-keeper:111984/01/1/1', DiaryKeeper::class);
        $form = $this->formFactory->create(UserIdentifierType::class, $diaryKeeperOne, ['csrf_protection' => false]);

        $form->submit($formData);
        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}
