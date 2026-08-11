<?php

namespace App\Tests\Form\Type\TravelDiary\Reflow\Journey;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Form\TravelDiary\CombinedReflowWizard\Journey\JourneyDetailsType;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class JourneyDetailsTypeTest extends AbstractKernelTypeTest
{
    private static array $validTimesData = [
        'times' => [
            'startTime' => ['hour' => '1', 'minute' => '59', 'am_or_pm' => 'pm'],
            'endTime' => ['hour' => '2', 'minute' => '25', 'am_or_pm' => 'pm'],
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $fixtures = $this->databaseTool->loadFixtures([UserFixtures::class]);
        $user = $fixtures->getReferenceRepository()->getReference('user:diary-keeper:adult', User::class);

        // TimesType needs a logged-in user for time format preferences
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get(TokenStorageInterface::class)->setToken($token);
    }

    private function createState(bool $isGoingHome): CombinedReflowState
    {
        $journey = new Journey();
        if ($isGoingHome) {
            $journey->setIsStartHome(false);
            $journey->setStartLocation('Somewhere');
            $journey->setIsEndHome(true);
        } else {
            $journey->setIsStartHome(true);
            $journey->setIsEndHome(false);
            $journey->setEndLocation('Somewhere');
        }

        $state = new CombinedReflowState();
        $state->setSubject($journey);
        return $state;
    }

    public function dataForm(): array
    {
        return [
            // Valid cases
            'Valid: times + purpose (non-home destination)' => [
                array_merge(self::$validTimesData, ['purpose' => ['purpose' => 'Go shopping']]),
                true, false,
            ],
            'Valid: times only (home destination, no purpose field)' => [
                self::$validTimesData,
                true, true,
            ],

            // Missing times
            'Invalid: missing start time' => [
                ['times' => [
                    'startTime' => ['hour' => '', 'minute' => '', 'am_or_pm' => 'pm'],
                    'endTime' => ['hour' => '2', 'minute' => '25', 'am_or_pm' => 'pm'],
                ]],
                false, true,
            ],
            'Invalid: missing end time' => [
                ['times' => [
                    'startTime' => ['hour' => '1', 'minute' => '59', 'am_or_pm' => 'pm'],
                    'endTime' => ['hour' => '', 'minute' => '', 'am_or_pm' => 'pm'],
                ]],
                false, true,
            ],

            'Invalid: empty purpose for non-home destination' => [
                array_merge(self::$validTimesData, ['purpose' => ['purpose' => '']]),
                false, false,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid, bool $isGoingHome): void
    {
        $state = $this->createState($isGoingHome);

        $form = $this->formFactory->create(JourneyDetailsType::class, $state, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
