<?php

namespace App\Tests\Form\Type\TravelDiary\Reflow\Stage;

use App\Entity\Journey\Journey;
use App\Form\TravelDiary\CombinedReflowWizard\Stage\AddAnotherType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class AddAnotherTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid: final stage' => [['is_final_stage' => 'true'], true],
            'Valid: add another' => [['is_final_stage' => 'false'], true],
            'Invalid: empty submission' => [[], false],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $journey = (new Journey())->setEndLocation('Somewhere');

        $form = $this->formFactory->create(AddAnotherType::class, null, [
            'csrf_protection' => false,
            'journey' => $journey,
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
