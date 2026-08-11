<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class DeleteJourneyTest extends AbstractWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures([
            StageFixtures::class
        ]);
    }

    public function dataDeleteJourney(): array {
        $journeyTimes = ['8:26 am', '4:00 pm'];
        return [
            [0, $journeyTimes],
            [1, $journeyTimes],
        ];
    }

    /**
     * @dataProvider dataDeleteJourney
     */
    public function testDeleteJourney(int $journeyToDelete, array $journeyTimes): void
    {
        $emailAddress = 'diary-keeper-adult@example.com';

        $this->client->request(Request::METHOD_GET, '/');
        $this->submitLoginForm($emailAddress, 'password');
        $this->assertEquals('/travel-diary', $this->client->getRequest()->getRequestUri());

        // First "View" link from the "My diary week" summary table
        $link = $this->client->getCrawler()
            ->filterXPath('//h2[contains(text(),"Test Diary Keeper (Adult)\'s diary week")]/following-sibling::dl//a[contains(text(),"View")]')
            ->eq(0)
            ->link();

        $this->client->click($link);

        $this->assertUrlEquals('/travel-diary/day-1');

        $daySummaryRowXPath = '//h1[contains(text(),"Day 1")]/following-sibling::dl/div';
        $getRows = fn(): Crawler => $this->client->getCrawler()->filterXPath($daySummaryRowXPath);
        $getTimes = fn(): array => $this->client->getCrawler()->filterXPath($daySummaryRowXPath."/dt")->each(fn($x) => $x->text());

        $rows = $getRows();
        $this->assertCount(2, $rows);
        $this->assertEquals($journeyTimes, $getTimes());

        // Click one of the view links...
        $link = $this->client->getCrawler()
            ->filterXPath('//h1[contains(text(),"Day 1")]/following-sibling::dl//a[contains(text(),"View")]')
            ->eq($journeyToDelete)
            ->link();

        $this->client->click($link);

        $journeyPageLink = $this->client->getRequest()->getRequestUri();

        // 1. Visit the delete page, but click cancel
        $this->client->clickLink('Delete this journey');
        $this->client->clickLink('Cancel');

        $this->assertEquals($journeyPageLink, $this->client->getRequest()->getRequestUri());

        unset($journeyTimes[$journeyToDelete]);
        $journeyTimes = array_values($journeyTimes);

        // 2. Visit the delete page and hit the button
        $this->client->clickLink('Delete this journey');
        $this->client->submitForm('Yes, delete journey');

        $this->assertUrlEquals('/travel-diary/day-1');
        $rows = $getRows();
        $this->assertCount(1, $rows);
        $this->assertEquals($journeyTimes, $getTimes());
    }
}
