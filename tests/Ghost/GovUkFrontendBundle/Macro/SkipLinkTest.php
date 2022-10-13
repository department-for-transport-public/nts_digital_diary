<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

class SkipLinkTest extends AbstractMacroTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('skip-link', []);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testSkipLink(array $fixture): void
    {
        $this->renderAndCompare('skipLink', $fixture);
    }
}