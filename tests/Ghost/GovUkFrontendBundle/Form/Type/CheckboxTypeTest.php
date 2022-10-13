<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use App\Tests\Ghost\GovUkFrontendBundle\Form\AbstractFormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\CheckboxType;

class CheckboxTypeTest extends AbstractFormTestCase
{
    public function checkboxFixtureProvider(): array
    {
        return $this->loadFixtures('checkboxes', fn($x) => strpos($x['name'], 'single option') === false);
    }

    /**
     * @dataProvider checkboxFixtureProvider
     */
    public function testCheckboxesFixtures(array $fixture): void
    {
        $this->createAndTestForm(
            CheckboxType::class,
            $this->getCheckboxData($fixture['options']['items'] ?? []),
            $this->mapJsonOptions($fixture['options'] ?? []),
            $fixture
        );
    }

    protected function getCheckboxData($items = [])
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
        // All of the options we want to support in CheckboxType
        $mappedOptions = ['items', 'fieldset', 'hint', 'classes', 'attributes', 'formGroup', 'describedBy'];
        $fixtureOptions = array_intersect_key($fixtureOptions, array_fill_keys($mappedOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        $formOptions['csrf_protection'] = false;
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'describedBy':
                    $formOptions['attr'] = $formOptions['attr'] ?? [];
                    $formOptions['attr']['aria-describedby'] = $value;
                    break;

                case 'items' :
                    if (count($value) !== 1) {
                        throw new \RuntimeException('Unsupported fixture');
                    }

                    foreach($value as $item) {
                        if ($item['text'] ?? $item['html'] ?? false) {
                            $formOptions['label'] = $item['text'] ?? $item['html'];
                            if ($item['html'] ?? null) {
                                $formOptions['label_html'] = true;
                            }
                        }

                        if ($item['value'] ?? null) {
                            $formOptions['value'] = $item['value'];
                        }

                        if ($item['hint']['text'] ?? false) {
                            $formOptions['help'] = $item['hint']['text'];
                        }

                        if ($item['disabled'] ?? false) {
                            $formOptions['disabled'] = $item['disabled'];
                        }

                        if ($item['attributes'] ?? false) {
                            $formOptions['attr'] = $item['attributes'];
                        }

                        if ($item['label'] ?? false) {
                            $formOptions['label_attr'] = $item['label']['attributes'] ?? [];
                            $formOptions['label_attr']['class'] = $item['label']['classes'] ?? '';
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