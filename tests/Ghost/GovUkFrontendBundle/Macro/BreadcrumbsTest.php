<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

class BreadcrumbsTest extends AbstractMacroTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('breadcrumbs', []);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testBreadcrumbs(array $fixture): void
    {
        $fixture['options']['lastItemIsCurrentPage'] = false;
        $this->renderAndCompare('breadcrumbs', $fixture);
    }
}