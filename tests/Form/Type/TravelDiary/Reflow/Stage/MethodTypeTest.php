<?php

namespace App\Tests\Form\Type\TravelDiary\Reflow\Stage;

use App\DataFixtures\NtsFixtures;
use App\Entity\Journey\Stage;
use App\Form\TravelDiary\CombinedReflowWizard\Stage\MethodType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class MethodTypeTest extends AbstractKernelTypeTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->databaseTool->loadFixtures([NtsFixtures::class]);
    }

    public function dataForm(): array
    {
        return [
            // Valid selections (methods with non-null code don't need "other" text)
            'Valid: walk (id=1)' => [['method' => '1'], true],
            'Valid: car (id=2)' => [['method' => '2'], true],
            'Valid: train (id=7)' => [['method' => '7'], true],

            // Required
            'Invalid: empty submission' => [[], false],

            // "Other" methods (code=null) require methodOther text
            'Invalid: other-private without text' => [['method' => '5'], false],
            'Invalid: other-public without text' => [['method' => '10'], false],
            'Valid: other-private with text' => [
                ['method' => '5', 'other-other-private' => 'Skateboard'],
                true,
            ],
            'Valid: other-public with text' => [
                ['method' => '10', 'other-other-public' => 'Rickshaw'],
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(array $formData, bool $expectedValid): void
    {
        $stage = new Stage();

        $form = $this->formFactory->create(MethodType::class, $stage, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }
}
