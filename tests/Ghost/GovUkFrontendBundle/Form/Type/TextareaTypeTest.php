<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use App\Tests\Ghost\GovUkFrontendBundle\Form\AbstractFormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;

class TextareaTypeTest extends AbstractFormTestCase
{
    public function fixtureProvider(): array
    {
        $ignoreFixtures = [];
        return $this->loadFixtures('textarea', $ignoreFixtures);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testTextareaFixtures($fixture): void
    {
        $this->createAndTestForm(
            TextareaType::class,
            $fixture['options']['value'] ?? null,
            $this->mapJsonOptions($fixture['options'] ?? []),
            $fixture
        );
    }

    protected function mapJsonOptions($fixtureOptions): array
    {
        // All of the options we want to support in TextareaType
        $mappedOptions = ['disabled', 'label', 'text', 'html', 'classes', 'attributes', 'hint', 'rows',
            'isPageHeading', 'formGroup', 'autocomplete', 'spellcheck',
        ];
        $fixtureOptions = array_intersect_key($fixtureOptions, array_fill_keys($mappedOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        $formOptions['csrf_protection'] = false;

        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'rows':
                    $formOptions['rows'] = $value;
                    break;
            }
        }

        return $formOptions;
    }
}