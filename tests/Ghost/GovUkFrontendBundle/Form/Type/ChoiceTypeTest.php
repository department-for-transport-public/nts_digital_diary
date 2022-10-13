<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use App\Tests\Ghost\GovUkFrontendBundle\Form\AbstractFormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;

class ChoiceTypeTest extends AbstractFormTestCase
{
    public function checkboxFixtureProvider(): array
    {
        $ignoreTests = [
            'with conditional items',
            'with conditional item checked',
            'with conditional items with special characters',
            'with optional form-group classes showing group error',
            'small with conditional reveal',
            'with label classes', // we can't easily do choice label attributes
            'multiple hints', // this one is stupid, it expects there to be an option with no label or value, but has a hint
            'label with attributes', // we can't easily do choice label attributes
            'fieldset params',
            'with divider and None',
            'with divider, None and conditional items',
        ];
        return $this->loadFixtures('checkboxes', $ignoreTests);
    }

    /**
     * @dataProvider checkboxFixtureProvider
     */
    public function testCheckboxesFixtures(array $fixture): void
    {
        $this->createAndTestForm(
            ChoiceType::class,
            $this->getCheckboxData($fixture['options']['items'] ?? []),
            array_merge([
                'expanded' => true,
                'multiple' => true,
            ],
            $this->mapJsonOptions($fixture['options'] ?? [])),
            $fixture
        );
    }

    protected function getCheckboxData($items = []): array
    {
        $data = [];
        foreach ($items as $item)
        {
            if ($item['checked'] ?? false) $data[] = $item['value'];
        }
        return $data;
    }



    public function radioFixtureProvider(): array
    {
        $ignoreTests = [
            'with a divider',
            'with conditional items',
            'with conditional items with special characters',
            'inline with conditional items',
            'with conditional item checked',
            'with optional form-group classes showing group error',
            'small with conditional reveal',
            'small with a divider',
            'fieldset with describedBy',
            'with hints on parent and items', // options without values/labels
            'fieldset params',
        ];
        return $this->loadFixtures('radios', $ignoreTests);
    }

    /**
     * @dataProvider radioFixtureProvider
     */
    public function testRadioFixtures(array $fixture): void
    {
        $this->createAndTestForm(
            ChoiceType::class,
            $this->getRadioData($fixture['options']['items'] ?? []),
            array_merge([
                'expanded' => true,
            ], $this->mapJsonOptions($fixture['options'] ?? [])),
            $fixture
        );
    }

    protected function getRadioData($items = []): ?string
    {
        foreach ($items as $item)
        {
            if ($item['checked'] ?? false) {
                return $item['value'];
            }
        }
        return null;
    }

    protected function mapJsonOptions($fixtureOptions): array
    {
        // All of the options we want to support in ChoiceType
        $mappedOptions = ['items', 'fieldset', 'hint', 'classes', 'attributes', 'formGroup'];
        $fixtureOptions = array_intersect_key($fixtureOptions, array_fill_keys($mappedOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        $formOptions['csrf_protection'] = false;
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'items' :
                    $formOptions['choices'] = [];
                    foreach($value as $item) {
                        if ($item['text'] ?? $item['html'] ?? false)
                        {
                            $formOptions['choices'][$item['text'] ?? $item['html']] = $item['value'];
                            if (!empty($item['html'])) {
                                $formOptions['choice_options'][$item['html']]['label_html'] = true;
                            }
                            if ($item['hint']['text'] ?? false) {
                                $formOptions['choice_options'][$item['text']]['help'] = $item['hint']['text'];
                            }
                            if ($item['disabled'] ?? false) {
//                                $formOptions['choice_attr'][$item['text']]['disabled'] = $item['disabled'];
                                $formOptions['choice_options'][$item['text']]['disabled'] = $item['disabled'];
                            }
                            if ($item['attributes'] ?? false) {
                                $formOptions['choice_attr'][$item['text']] = $item['attributes'];
                            }
                            if ($item['label'] ?? false) {
                                $formOptions['choice_options'][$item['text']]['label_attr'] = $item['label']['attributes'] ?? [];
                                $formOptions['choice_options'][$item['text']]['label_attr']['class'] = $item['label']['classes'] ?? '';

                            }
                        }
                    }
                    break;

                case 'fieldset' :
                    $formOptions['label'] = $value['legend']['text'] ?? $value['legend']['html'] ?? false;
                    $formOptions['label_html'] = !empty($value['legend']['html']);
                    if ($value['legend']['classes'] ?? false)
                    {
                        $formOptions['label_attr'] = $formOptions['label_attr'] ?? [];
                        $formOptions['label_attr']['class'] = trim(($formOptions['label_attr']['class'] ?? "") . " {$value['legend']['classes']}");
                    }
                    $formOptions['label_is_page_heading'] = $value['legend']['isPageHeading'] ?? false;
                    break;
            }
        }

        return $formOptions;
    }
}