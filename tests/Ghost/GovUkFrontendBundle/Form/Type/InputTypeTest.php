<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use App\Tests\Ghost\GovUkFrontendBundle\Form\AbstractFormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;

class InputTypeTest extends AbstractFormTestCase
{
    public function fixtureProvider(): array
    {
        $ignoreFixtures = [
            'with prefix with attributes',
            'with suffix with attributes',
            'with prefix with classes',
            'with suffix with classes',
        ];
//        function ($fixture) {
//            return false;
//            // suffix/prefix not supported
////            return preg_match('(suffix|prefix)', $fixture['name']) === 1;
//        };
        return $this->loadFixtures('input', $ignoreFixtures);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testInputFixtures(array $fixture)
    {
        $this->createAndTestForm(
            $this->getInputType($fixture),
            $fixture['options']['value'] ?? null,
            $this->mapJsonOptions($fixture['options'] ?? []),
            $fixture
        );
    }

    protected function getInputType(array $fixture): string
    {
        switch (true)
        {
            case ($fixture['options']['type'] ?? false) === 'number' :
            case !empty($fixture['options']['inputmode']) :
                return NumberType::class;
        }
        return InputType::class;
    }

    protected function mapJsonOptions($fixtureOptions): array
    {
        // All of the options we want to support in TextareaType
        $ignoredOptions = [];
        $fixtureOptions = array_diff_key($fixtureOptions, array_fill_keys($ignoredOptions, 0));

$isThatText = ($fixtureOptions['name'] ?? null) == "with-inputmode";

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        $formOptions['csrf_protection'] = false;

        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'suffix':
                    $formOptions['suffix'] = $value['text'] ?? $value['html'] ?? false;
                    $formOptions['suffix_html'] = !empty($value['html']);
                    break;
                case 'prefix':
                    $formOptions['prefix'] = $value['text'] ?? $value['html'] ?? false;
                    $formOptions['prefix_html'] = !empty($value['html']);
                    break;
                case 'inputmode':
                    $inputType = $this->getInputType($fixtureOptions);

                    if ($inputType === NumberType::class) {
                        $formOptions['is_decimal'] = true;
                    } else {
                        $formOptions['attr'] = array_merge(
                            $formOptions['attr'] ?? [],
                            [
                                'inputmode' => $value,
                            ]);
                    }
                    break;
                case 'type':
                    $formOptions['type'] = $value;
                    break;
            }
        }

        return $formOptions;
    }
}