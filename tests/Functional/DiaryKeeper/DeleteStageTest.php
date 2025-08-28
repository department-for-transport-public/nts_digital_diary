<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\HttpFoundation\Request;

class DeleteStageTest extends AbstractWebTestCase
{
    protected KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

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

        $this->client->request(Request::METHOD_GET, '/');
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

        $getPanels = fn(): Crawler => $this->client->getCrawler()->filterXPath("//div[@id='step-by-step-navigation']//div[contains(concat(' ',@class,' '), ' app-step-nav__header ')]");
        $getTransportMethods = fn(Crawler $panels): array => $panels->each(fn($n) => $n->filterXPath("//span[contains(concat(' ',@class,' '), ' app-step-nav__title-text ')]")->innerText());
        $getDeleteLink = fn(Crawler $panels, int $panelToDelete): Link => $panels->eq($panelToDelete)->filterXPath('//a[contains(text(), "Delete this stage")]')->link();

        $panels = $getPanels();

        $expectedTransportMethods = ['Bus/Coach: London bus', 'Walk or run'];
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
