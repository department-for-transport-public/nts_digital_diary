<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

class HeaderTest extends AbstractMacroTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('header', []);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testHeader(array $fixture): void
    {
        $this->renderAndCompare('header', $fixture);
    }
}