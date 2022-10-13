<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

class PhaseBannerTest extends AbstractMacroTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('phase-banner', []);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testPhaseBanner(array $fixture): void
    {
        $this->renderAndCompare('phaseBanner', $fixture);
    }
}