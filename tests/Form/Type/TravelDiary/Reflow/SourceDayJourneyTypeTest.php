<?php

namespace App\Tests\Form\Type\TravelDiary\Reflow;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Form\TravelDiary\CombinedReflowWizard\SourceDayJourneyType;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use App\Utility\TravelDiary\DuplicateJourneyHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SourceDayJourneyTypeTest extends AbstractKernelTypeTest
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

    public function dataForm(): array
    {
        return [
            // Day selection required first
            'Invalid: empty submission' => [[], false],

            // Valid day selected, but no journey selected
            'Invalid: day selected without journey' => [
                ['sourceDayNumber' => 'day-1'],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $state = new CombinedReflowState();
        $state->setSubject(new Journey());

        $form = $this->formFactory->create(SourceDayJourneyType::class, $state, [
            'csrf_protection' => false,
            'journey_type' => DuplicateJourneyHelper::JOURNEY_TYPE_REPEAT,
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }

    public function testValidDayAndJourneySelection(): void
    {
        $sourceJourney = $this->fixtures->getReference('journey:1', Journey::class);

        $state = new CombinedReflowState();
        $state->setSubject(new Journey());

        $form = $this->formFactory->create(SourceDayJourneyType::class, $state, [
            'csrf_protection' => false,
            'journey_type' => DuplicateJourneyHelper::JOURNEY_TYPE_REPEAT,
        ]);
        $form->submit([
            'sourceDayNumber' => 'day-1',
            'day-1-journey' => $sourceJourney->getId(),
        ]);

        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
    }
}
