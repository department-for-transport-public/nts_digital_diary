<?php

namespace App\Tests\Form\Type\TravelDiary\Reflow\Return;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Form\TravelDiary\CombinedReflowWizard\Return\TargetDayType;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TargetDayTypeTest extends AbstractKernelTypeTest
{
    private ReferenceRepository $fixtures;

    public function setUp(): void
    {
        parent::setUp();

        $result = $this->databaseTool->loadFixtures([StageFixtures::class]);
        $this->fixtures = $result->getReferenceRepository();

        $user = $this->fixtures->getReference('user:diary-keeper:adult', User::class);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get(TokenStorageInterface::class)->setToken($token);
    }

    private function createState(): CombinedReflowState
    {
        $sourceJourney = $this->fixtures->getReference('journey:1', Journey::class);

        $state = new CombinedReflowState();
        $state->sourceJourneyId = $sourceJourney->getId();
        $state->setSubject(new Journey());
        return $state;
    }

    public function dataForm(): array
    {
        return [
            'Valid: select day 3' => [['diaryDay' => 'day-3'], true],
            // BUG: Same property_path cascade issue as purpose validation — NotNull on
            // Journey::$diaryDay doesn't fire through CombinedReflowState indirection.
            'Invalid: empty submission' => [[], false],
            'Invalid: nonsense value' => [['diaryDay' => 'banana'], false],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $state = $this->createState();

        $form = $this->formFactory->create(TargetDayType::class, $state, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
