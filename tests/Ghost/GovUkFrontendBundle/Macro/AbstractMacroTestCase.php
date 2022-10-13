<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

use App\Tests\Ghost\GovUkFrontendBundle\AbstractFixtureTest;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;

abstract class AbstractMacroTestCase extends AbstractFixtureTest
{
    protected Environment $twig;

    public function setUp(): void
    {
        $this->twig = static::getContainer()->get('twig');
    }

    public function renderAndCompare(string $macroName, array $fixture): void
    {
        $templateSource = <<<EOT
{%- import "@GhostGovUkFrontend/components/macros.html.twig" as m -%}
{{- m.{$macroName}(params) -}}
EOT;

        $template = $this->twig->createTemplate($templateSource);
        $html = $template->render(['params' => $fixture['options']]);

        $expectedCrawler = new Crawler();
        $expectedCrawler->addHtmlContent($fixture['html']);

        $actualCrawler = new Crawler();
        $actualCrawler->addHtmlContent($html);

        $this->assertStructuresMatch($expectedCrawler, $actualCrawler, $fixture['name']);
    }
}