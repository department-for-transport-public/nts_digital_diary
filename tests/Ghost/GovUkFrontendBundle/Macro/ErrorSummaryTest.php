<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Macro;

class ErrorSummaryTest extends AbstractMacroTestCase
{
    public function fixtureProvider(): array
    {
        return $this->loadFixtures('error-summary', []);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testErrorSummary(array $fixture): void
    {
        $this->renderAndCompare('errorSummary', $fixture);
    }
}