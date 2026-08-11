<?php

namespace App\Tests\Form\Type\TravelDiary\JourneyWizard;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Form\TravelDiary\JourneyWizard\LocationsType;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LocationsTypeTest extends AbstractKernelTypeTest
{
    public function setUp(): void
    {
        parent::setUp();

        $fixtures = $this->databaseTool->loadFixtures([UserFixtures::class]);
        $user = $fixtures->getReferenceRepository()->getReference('user:diary-keeper:adult', User::class);

        // LocationsType needs a logged-in user for common locations
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get(TokenStorageInterface::class)->setToken($token);
    }

    public function dataForm(): array
    {
        return [
            // Valid cases
            'Valid: home to other' => [
                ['start_choice' => 'home', 'end_choice' => 'other', 'endLocation' => 'Work'],
                true,
            ],
            'Valid: other to home' => [
                ['start_choice' => 'other', 'startLocation' => 'Work', 'end_choice' => 'home'],
                true,
            ],
            'Valid: other to other' => [
                ['start_choice' => 'other', 'startLocation' => 'Work', 'end_choice' => 'other', 'endLocation' => 'Shops'],
                true,
            ],

            // Required fields
            'Invalid: empty submission' => [[], false],
            'Invalid: missing end choice' => [
                ['start_choice' => 'home'],
                false,
            ],
            'Invalid: missing start choice' => [
                ['end_choice' => 'home'],
                false,
            ],

            // Custom location required when "other" selected
            'Invalid: start=other but no location' => [
                ['start_choice' => 'other', 'startLocation' => '', 'end_choice' => 'home'],
                false,
            ],
            'Invalid: end=other but no location' => [
                ['start_choice' => 'home', 'end_choice' => 'other', 'endLocation' => ''],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $journey = new Journey();

        $form = $this->formFactory->create(LocationsType::class, $journey, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
