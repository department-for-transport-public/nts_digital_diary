<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form;

use App\Tests\Ghost\GovUkFrontendBundle\AbstractFixtureTest;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

abstract class AbstractFormTestCase extends AbstractFixtureTest
{
    protected function createAndTestForm($formClass, $formData, $formOptions, $fixture): void
    {
        self::bootKernel();

        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $form = $formFactory->create($formClass, $formData, $formOptions);

        if ($fixture['options']['errorMessage'] ?? false) {
            $form->addError(new FormError($fixture['options']['errorMessage']['text']));
        }

        $this->renderAndCompare($fixture, $form);
    }

    /**
     * @param $fixture
     * @param FormInterface $componentForm
     */
    private function renderAndCompare($fixture, FormInterface $componentForm): void
    {
        /** @var Environment $twig */
        $twig = static::getContainer()->get('twig');

        $renderedHtml = '';
        // render it
        try {
            $renderedHtml = $twig->render($twig->createTemplate("{{ form_row(form) }}"), ['form' => $componentForm->createView()]);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->fail($e);
        }

        // compare results
        $fixtureCrawler = new Crawler();
        $fixtureCrawler->addHtmlContent($fixture['html']);

        $renderCrawler = new Crawler();
        $renderCrawler->addHtmlContent($renderedHtml);

        // Select the children of the body elements (ie the content of the fixture/what we've rendered)
        // and assert they're the same
        $this->assertStructuresMatch(
            $fixtureCrawler->filter('body')->children(),
            $renderCrawler->filter('body')->children(),
            $fixture['name']
        );
    }

    /**
     * Map some common fixture options
     */
    protected function mapJsonOptions($fixtureOptions): array
    {
        $formOptions = ['attr' => [], 'label' => false];
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'text' :
                    $formOptions['label'] = $value;
                    break;
                case 'html' :
                    $formOptions['label'] = $value;
                    $formOptions['label_html'] = true;
                    break;
                case 'label' :
                    $formOptions['label'] = $value['text'] ?? $value['html'] ?? null;
                    $formOptions['label_html'] = !empty($value['html']);
                    if ($value['isPageHeading'] ?? false) $formOptions['label_is_page_heading'] = true;
                    break;
                case 'classes' :
                    $formOptions['attr']['class'] = trim(($formOptions['attr']['class'] ?? "") . " " . $value);
                    break;
                case 'attributes' :
                    $formOptions['attr'] = array_merge($formOptions['attr'], $value);
                    break;
                case 'disabled' :
                    $formOptions[$option] = $value;
                    break;
                case 'hint' :
                    $formOptions['help'] = $value['text'] ?? null;
                    break;
                case 'formGroup' :
                    if ($value['classes'] ?? false) {
                        $formOptions['row_attr']['class'] = trim(($formOptions['row_attr']['class'] ?? "") . " " . $value['classes']);
                    }
                    break;
                case 'autocomplete' :
                    $formOptions['attr']['autocomplete'] = $value;
                    break;
                case 'spellcheck' :
                    $formOptions['attr']['spellcheck'] = $value ? 'true' : 'false';
                    break;

                default :
                    break;
            }
        }

        return $formOptions;
    }
}