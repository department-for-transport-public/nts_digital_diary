<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use App\Tests\Ghost\GovUkFrontendBundle\Form\AbstractFormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\FileUploadType;
use Symfony\Component\HttpFoundation\File\File;

class FileUploadTypeTest extends AbstractFormTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('file-upload', []);
    }

    /**
     * @dataProvider fixtureProvider
     * @param $fixture
     */
    public function testFileUploadFixtures($fixture): void
    {
        $this->createAndTestForm(
            FileUploadType::class,
            isset($fixture['options']['value'])
                ? new File($fixture['options']['value'], false)
                : null,
            $this->mapJsonOptions($fixture['options'] ?? []),
            $fixture
        );
    }

    protected function mapJsonOptions($fixtureOptions): array
    {
        // All of the options we want to support in TextareaType
        $ignoredOptions = [];
        $fixtureOptions = array_diff_key($fixtureOptions, array_fill_keys($ignoredOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        $formOptions['csrf_protection'] = false;

        return $formOptions;
    }
}