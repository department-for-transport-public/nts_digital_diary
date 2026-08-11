<?php

namespace App\Tests\Form\Type\TravelDiary\Reflow;

use App\Form\TravelDiary\CombinedReflowWizard\WizardTypeType;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Repository\DiaryDayRepository;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class WizardTypeTypeTest extends AbstractKernelTypeTest
{
    private function setUpWithJourneys(bool $hasJourneys): void
    {
        $repo = $this->createMock(DiaryDayRepository::class);
        $repo->method('getJourneyCountForUser')->willReturn($hasJourneys ? 1 : 0);

        $this->queueFormTypeForPreload(new WizardTypeType($repo));
        $this->preloadFormTypes();
    }

    public function dataForm(): array
    {
        return [
            'Valid: add' => [['wizardType' => CombinedReflowState::WIZARD_TYPE__ADD], true, true],
            'Valid: add (no existing journeys)' => [['wizardType' => CombinedReflowState::WIZARD_TYPE__ADD], true, false],
            'Valid: repeat (has journeys)' => [['wizardType' => CombinedReflowState::WIZARD_TYPE__REPEAT], true, true],
            'Valid: return (has journeys)' => [['wizardType' => CombinedReflowState::WIZARD_TYPE__RETURN], true, true],

            'Valid: empty submission (wizard guards handle null)' => [[], true, true],
            'Invalid: nonsense value' => [['wizardType' => 'nonsense'], false, true],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid, bool $hasJourneys): void
    {
        $this->setUpWithJourneys($hasJourneys);

        $form = $this->formFactory->create(WizardTypeType::class, null, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
