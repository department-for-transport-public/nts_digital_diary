<?php

namespace App\Tests\Form\Type\TravelDiary\Reflow\Return;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\Form\TravelDiary\CombinedReflowWizard\Return\JourneyDetailsType;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ReturnJourneyDetailsTypeTest extends AbstractKernelTypeTest
{
    private static array $validData = [
        'times' => [
            'startTime' => ['hour' => '1', 'minute' => '59', 'am_or_pm' => 'pm'],
            'endTime' => ['hour' => '2', 'minute' => '25', 'am_or_pm' => 'pm'],
        ],
        'purpose' => ['purpose' => 'Return trip'],
        'isSameStages' => 'true',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $fixtures = $this->databaseTool->loadFixtures([UserFixtures::class]);
        $user = $fixtures->getReferenceRepository()->getReference('user:diary-keeper:adult', User::class);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get(TokenStorageInterface::class)->setToken($token);
    }

    private function createState(): CombinedReflowState
    {
        $method = Method::method(1, 1, 'walk', 'other', 'private');
        $journey = (new Journey())->setIsStartHome(true)->setIsEndHome(false)->setEndLocation('Work');
        $journey->addStage((new Stage())->setMethod($method));

        $state = new CombinedReflowState();
        $state->setSubject($journey);
        return $state;
    }

    public function dataForm(): array
    {
        return [
            'Valid: complete data' => [self::$validData, true],
            'Valid: same stages false' => [
                array_merge(self::$validData, ['isSameStages' => 'false']),
                true,
            ],

            // BUG: isSameStages constraint uses group 'wizard.repeat-journey.same-stages' but
            // this form uses 'wizard.return-journey.same-stages' — no matching constraint exists.
            // Missing isSameStages should be invalid but passes.
            'Invalid: missing isSameStages' => [
                ['times' => self::$validData['times'], 'purpose' => ['purpose' => 'Trip']],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $state = $this->createState();

        $form = $this->formFactory->create(JourneyDetailsType::class, $state, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
