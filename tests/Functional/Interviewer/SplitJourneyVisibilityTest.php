<?php

namespace App\Tests\Functional\Interviewer;

use App\Tests\DataFixtures\TestSpecific\SplitJourneyTestFixtures;
use App\Utility\Test\CrawlerTableHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\RouterInterface;


// Tests visibility of Split Journey links on various "View Journey" pages, testing controller, templates and
// implicitly voters
class SplitJourneyVisibilityTest extends AbstractInterviewerDiaryKeeperTest
{
    protected KernelBrowser $client;
    protected ReferenceRepository $referenceRepository;
    protected RouterInterface $router;

    public function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();

        $databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $fixtures = $databaseTool->loadFixtures([
            SplitJourneyTestFixtures::class
        ]);

        $this->referenceRepository = $fixtures->getReferenceRepository();
        $this->router = $container->get(RouterInterface::class);
    }

    public function dataJourneySplitVisibilityForInterviewerImpersonating(): array
    {
        // See JourneySplitterTextFixtures for explanations as to why these particular fixtures will/won't have a
        // journey splitter link
        return [
            [true, 'journey:1'],
            [true, 'journey:2'],
            [true, 'journey:3'],
            [true, 'journey:4'],
            [false, 'journey:5'],
            [false, 'journey:6'],
            [false, 'journey:7'],
            [false, 'journey:8'],
            [false, 'journey:9'],
            [false, 'journey:10'],
        ];
    }

    /**
     * @dataProvider dataJourneySplitVisibilityForInterviewerImpersonating
     */
    public function testJourneySplitJourneyVisibilityForInterviewerImpersonating(bool $expectedToHaveSplitJourneyLink, string $journeyReference): void
    {
        $this->logInAsInterviewerAndDrillDownToUsersHouseholdPage('diary-keeper-adult@example.com');

        // Click the "impersonate" link
        $tableHelper = new CrawlerTableHelper($this->client->getCrawler());
        $impersonateUrl = $tableHelper->getLinkUrlForRowMatching('Impersonate', [
            'Name' => 'Test Diary Keeper (Adult)',
        ], false);
        $this->client->request('GET', $impersonateUrl);

        $this->assertSplitJourneyLinkState($expectedToHaveSplitJourneyLink, $journeyReference);
    }


    public function dataJourneySplitVisibilityForDiaryKeeper(): array
    {
        // Diary keepers should not see a split journey link (interviewer only)
        return [
            [false, 'journey:1'],
            [false, 'journey:2'],
            [false, 'journey:3'],
            [false, 'journey:4'],
            [false, 'journey:5'],
            [false, 'journey:6'],
            [false, 'journey:7'],
            [false, 'journey:8'],
            [false, 'journey:9'],
            [false, 'journey:10'],
        ];
    }

    /**
     * @dataProvider dataJourneySplitVisibilityForDiaryKeeper
     */
    public function testJourneySplitVisibilityForDiaryKeeper(bool $expectedToHaveSplitJourneyLink, string $journeyReference): void
    {
        $this->client->request('GET', '/');
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');
        $this->assertEquals('/travel-diary', $this->client->getRequest()->getRequestUri());

        $this->assertSplitJourneyLinkState($expectedToHaveSplitJourneyLink, $journeyReference);
    }

    protected function assertSplitJourneyLinkState(bool $expectedToHaveSplitJourneyLink, string $journeyReference): void
    {
        $journey = $this->referenceRepository->getReference($journeyReference);

        $this->client->request('GET', $this->router->generate('traveldiary_journey_view', [
            'journeyId' => $journey->getId(),
        ]));

        $hasSplitJourneyLink = str_contains(
            $this->client->getCrawler()->html(),
            'Split journey (Interviewer only)'
        );

        $this->assertEquals($expectedToHaveSplitJourneyLink, $hasSplitJourneyLink);
    }
}
