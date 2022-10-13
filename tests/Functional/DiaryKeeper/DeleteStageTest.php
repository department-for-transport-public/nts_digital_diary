<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class DeleteStageTest extends AbstractWebTestCase
{
    protected KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures([
            StageFixtures::class
        ]);
    }

    public function dataDeleteStage(): array {
        return [
            [0],
            [1],
        ];
    }

    /**
     * @dataProvider dataDeleteStage
     */
    public function testDeleteStage(int $panelToDelete): void
    {
        $emailAddress = 'diary-keeper-adult@example.com';

        $this->client->request('GET', '/');
        $this->submitLoginForm($emailAddress, 'password');
        $this->assertEquals('/travel-diary', $this->client->getRequest()->getRequestUri());

        // First "View" link from the "My diary week" summary table
        $link = $this->client->getCrawler()
            ->filterXPath('//h2[contains(text(),"My diary week")]/following-sibling::dl//a[contains(text(),"View")]')
            ->eq(0)
            ->link();

        $this->client->click($link);

        $this->assertEquals('/travel-diary/day-1', $this->client->getRequest()->getRequestUri());

        // Second "View" link from the Day 1 summary table (this one should have 2 stages)
        $link = $this->client->getCrawler()
            ->filterXPath('//h1[contains(text(),"Day 1")]/following-sibling::dl//a[contains(text(),"View")]')
            ->eq(1)
            ->link();

        $this->client->click($link);

        $getPanels = fn() => $this->client->getCrawler()->filterXPath('//h2[contains(text(),"Stage")]/following-sibling::div/section');
        $getTransportMethods = fn(Crawler $panels) => $panels->each(fn($n) => $n->filterXPath('//dt[contains(text(),"Transport method")]/following-sibling::dd')->text());
        $getDeleteLink = fn(Crawler $panels, int $panelToDelete) => $panels->eq($panelToDelete)->filterXPath('//a[contains(text(), "Delete this stage")]')->link();

        $panels = $getPanels();

        $expectedTransportMethods = ['Bus/Coach', 'Walk or run'];
        $this->assertEquals($expectedTransportMethods, $getTransportMethods($panels));

        // 1. Visit the delete page, but click cancel
        $this->client->click($getDeleteLink($panels, $panelToDelete));
        $this->client->clickLink('Cancel');
        $this->assertEquals(array_values($expectedTransportMethods), $getTransportMethods($getPanels()));

        // 2. Visit the delete page and hit the button
        $this->client->click($getDeleteLink($getPanels(), $panelToDelete));
        $this->client->submitForm('Yes, delete stage');

        unset($expectedTransportMethods[$panelToDelete]);
        $this->assertEquals(array_values($expectedTransportMethods), $getTransportMethods($getPanels()));
    }
}
