<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

class TagTest extends AbstractMacroTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('tag', []);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testTag(array $fixture): void
    {
        $this->renderAndCompare('tag', $fixture);
    }
}