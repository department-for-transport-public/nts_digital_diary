<?php

namespace App\Tests\Ghost\GovUkFrontendBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractFixtureTest extends WebTestCase
{
    /**
     * @param $component string the name of the component
     * @param $ignoreTests array | callable which tests should be ignored
     */
    protected function loadFixtures(string $component, $ignoreTests): array
    {
        $file = __DIR__ . "/../../../node_modules/govuk-frontend/govuk/components/${component}/fixtures.json";
        $fixtures = json_decode(file_get_contents($file), true);
        $this->assertEquals($component, $fixtures['component']);
        $fixtures = $fixtures['fixtures'];

        $activeFixtures = [];

        foreach ($fixtures as $fixture)
        {
            if (
                (is_array($ignoreTests) && in_array($fixture['name'] ?? '', $ignoreTests)) ||
                (is_callable($ignoreTests) && $ignoreTests($fixture))
            ) {
                // ignore this test
                continue;
            }

            // wrap this test in an array, so it can be used in @dataProvider
            $activeFixtures[$fixture['name']] = [$fixture];
        }

        return $activeFixtures;
    }

    /**
     * Assert that the two Crawlers have the same content
     */
    protected function assertStructuresMatch(Crawler $expected, Crawler $actual, string $fixtureName = '')
    {
        // check the nodes have the same name and number of children
        $this->assertEquals(
            $expected->nodeName(),
            $actual->nodeName(),
            "{$fixtureName}: {$actual->nodeName()}.{$actual->attr('class')}"
        );

        $this->assertEquals(
            $expected->children()->count(),
            $actual->children()->count(),
            "{$fixtureName}: {$actual->nodeName()}.{$actual->attr('class')}"
        );

        // get the text for this node only (by subtracting the text for its children), and check they're the same
        $expectedNodeText = trim(str_replace($expected->children()->text('', true), '', $expected->text(null, true)));
        $actualNodeText = trim(str_replace($actual->children()->text('', true), '', $actual->text(null, true)));
        $this->assertEquals(
            $expectedNodeText,
            $actualNodeText,
            "{$fixtureName}: {$actual->nodeName()}.{$actual->attr('class')}"
        );

        // Check the attributes are the same
        $ignoreAttributes = ['id', 'name', 'for', 'aria-describedby', 'xmlns'];
        foreach($expected->getNode(0)->attributes as $attributeIndex => $expectedAttribute) {
            if (in_array($expectedAttribute->name, $ignoreAttributes)) continue;
            /** @var \DOMAttr $expectedAttribute */
            $actualAttribute = $actual->attr($expectedAttribute->name);
            $this->assertEquals(
                $expectedAttribute->value,
                $actualAttribute,
                "{$fixtureName}: {$actual->nodeName()}[{$expectedAttribute->name}={$expectedAttribute->value}]"
            );
        }

        // traverse the tree of $expected
        $actualChildren = $actual->children();
        $expectedChildren = $expected->children();

        for($childIndex = 0; $childIndex < $expectedChildren->count(); $childIndex++)
        {
            $actualChild = $actualChildren->eq($childIndex);
            $expectedChild = $expectedChildren->eq($childIndex);
            $this->assertStructuresMatch($expectedChild, $actualChild, $fixtureName);
        }
    }
}