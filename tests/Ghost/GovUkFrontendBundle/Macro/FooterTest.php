<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

class FooterTest extends AbstractMacroTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('footer', []);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testFooter(array $fixture): void
    {
        $this->renderAndCompare('footer', $fixture);
    }
}