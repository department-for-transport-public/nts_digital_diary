<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

class DetailsTest extends AbstractMacroTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('details', []);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testDetails(array $fixture): void
    {
        $this->renderAndCompare('details', $fixture);
    }
}