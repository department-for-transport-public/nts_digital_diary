<?php

namespace App\Tests\Functional\Interviewer;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Tests\DataFixtures\TestSpecific\SplitJourneyTestFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Utility\Test\CrawlerTableHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class SplitJourneyTest extends AbstractProceduralWizardTest
{
    protected RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = self::getContainer()->get(RouterInterface::class);
    }

    protected function performTest(
        string $journeyFixtureRef,
        bool   $isHomeToHomeJourney,
        string $newMidPoint,
        array $expectedOutgoing,
        array $expectedReturn,
        bool $expectedReturnOnNextDay
    ): void {
        $journeyFixture = $this->getFixtureByReference($journeyFixtureRef);
        $this->assertInstanceOf(Journey::class, $journeyFixture);

        $url = fn(string $suffix) => "#/travel-diary/journey/[0-9A-Z]+{$suffix}#";
        $options = [
            PathTestAction::OPTION_EXPECTED_PATH_REGEX => true
        ];

        $this->formTestAction(
            $url('/split-journey/introduction'),
            'form_button_group_continue',
            [
                new FormTestCase([]),
            ],
            $options
        );

        $midpointTestCases = [
            new FormTestCase([], ['#midpoint_midpoint_choice']),
            new FormTestCase([
                'midpoint[midpoint_choice]' => 'other',
            ], ['#midpoint_midpointLocation']),
        ];

        if ($isHomeToHomeJourney) {
            // We can't cheat - this ends up getting mapped as if we tried to choose "home" from the choice list, but
            // since "home" is disabled, it will be as if no choice was made at all.
            $midpointTestCases[] = new FormTestCase([
                'midpoint[midpoint_choice]' => 'home',
            ], ['#midpoint_midpoint_choice']);
        }

        $midpointTestCases[] = new FormTestCase([
            'midpoint[midpoint_choice]' => 'other',
            'midpoint[midpointLocation]' => $newMidPoint,
        ]);

        $this->formTestAction(
            $url('/split-journey/midpoint'),
            'midpoint_button_group_continue',
            $midpointTestCases,
            $options
        );

        if (!$isHomeToHomeJourney) {
            if ($newMidPoint === 'Home') {
                $purposeTests = [
                    new FormTestCase([]), // The purpose will be pre-filled
                ];
            } else {
                $purposeTests = [
                    new FormTestCase([], ['#purpose_purpose']),
                    new FormTestCase([
                        'purpose[purpose]' => 'To test'
                    ]),
                ];
            }

            $this->formTestAction(
                $url('/split-journey/purpose'),
                'purpose_button_group_continue',
                $purposeTests,
                $options);
        }

        $this->pathTestAction("/travel-diary/day-1");

        // Test values on day page...
        $returnJourneyDay = $expectedReturnOnNextDay ? 2 : 1;
        $assertRowExistsAndMatchesTitle = function(string $journeyStartTime, string $expectedTitle, int $dayNumber)
        {
            $this->callbackTestAction(function (Context $context) use ($journeyStartTime, $expectedTitle, $dayNumber) {
                $xpath = '//dt[contains(text(),"' . $journeyStartTime . '")]/following-sibling::dd';
                $node = $this->getNodeFromDaySummaryPage($context, $dayNumber, $xpath);
                $this->assertEquals($expectedTitle, $node->getText());
            });
        };

        $assertRowExistsAndMatchesTitle(
            $expectedOutgoing['startTime'],
            $expectedOutgoing['startLocation']." to ".$expectedOutgoing['endLocation'].", 1 stage",
            1
        );
        $assertRowExistsAndMatchesTitle(
            $expectedReturn['startTime'],
            $expectedReturn['startLocation']." to ".$expectedReturn['endLocation'].", 1 stage",
            $returnJourneyDay
        );

        // Test values on individual journey pages...
        $assertThatJourneyViewMatches = function(array $expectedJourneyData, int $dayNumber)
        {
            $this->callbackTestAction(function (Context $context) use ($expectedJourneyData, $dayNumber) {
                // Get view link for journey
                $xpath = '//dt[contains(text(),"' . $expectedJourneyData['startTime'] . '")]/following-sibling::dd/following-sibling::dd/a';
                $node = $this->getNodeFromDaySummaryPage($context, $dayNumber, $xpath);

                $client = $context->getClient();
                $client->request('GET', $node->getAttribute('href'));

                $this->assertEquals(
                    $expectedJourneyData['purpose'],
                    $this->getNode($context, '//dt[contains(text(),"Journey purpose")]/following-sibling::dd')->getText()
                );

                $this->assertEquals(
                    $expectedJourneyData['startLocation']." (".$expectedJourneyData['startTime'].")",
                    $this->getNode($context, '//dt[contains(text(),"Start Location / Time")]/following-sibling::dd')->getText()
                );

                $this->assertEquals(
                    $expectedJourneyData['endLocation']." (".$expectedJourneyData['endTime'].")",
                    $this->getNode($context, '//dt[contains(text(),"End Location / Time")]/following-sibling::dd')->getText()
                );
            });
        };

        $assertThatJourneyViewMatches($expectedOutgoing, 1);
        $assertThatJourneyViewMatches($expectedReturn, $returnJourneyDay);
    }

    public function wizardData(): array
    {
        return
            [
                'Day 1 Home to Home' => [
                    'journey:1',
                    '4:00pm',
                    true,
                    'Banana',
                    ['startLocation' => 'Home', 'endLocation' => 'Banana', 'startTime' => '4:00pm', 'endTime' => '4:30pm', 'purpose' => 'walk the dog'],
                    ['startLocation' => 'Banana', 'endLocation' => 'Home', 'startTime' => '4:30pm', 'endTime' => '5:00pm', 'purpose' => Journey::TO_GO_HOME],
                ],
                'Day 1 Wobble to Wobble' => [
                    'journey:1',
                    '5:00pm',
                    false,
                    'Banana',
                    ['startLocation' => 'Wobble', 'endLocation' => 'Banana', 'startTime' => '5:00pm', 'endTime' => '5:30pm', 'purpose' => 'shopping'],
                    ['startLocation' => 'Banana', 'endLocation' => 'Wobble', 'startTime' => '5:30pm', 'endTime' => '6:00pm', 'purpose' => 'To test'],
                ],
                // Expect to see the newly split return journey on day 2
                'Day 1 Home to Home with midpoint crossing day boundary' => [
                    'journey:4',
                    '11:45pm',
                    true,
                    'Banana',
                    ['startLocation' => 'Home', 'endLocation' => 'Banana', 'startTime' => '11:45pm', 'endTime' => '12:00am', 'purpose' => 'walk the dog'],
                    ['startLocation' => 'Banana', 'endLocation' => 'Home', 'startTime' => '12:00am', 'endTime' => '12:15am', 'purpose' => Journey::TO_GO_HOME],
                    true,
                ],
                // Expect to see the outgoing split journey purpose changed to "Go home"
                'Day 1 Wobble to Wobble with new midpoint being Home' => [
                    'journey:1',
                    '5:00pm',
                    false,
                    'Home',
                    ['startLocation' => 'Wobble', 'endLocation' => 'Home', 'startTime' => '5:00pm', 'endTime' => '5:30pm', 'purpose' => Journey::TO_GO_HOME],
                    ['startLocation' => 'Home', 'endLocation' => 'Wobble', 'startTime' => '5:30pm', 'endTime' => '6:00pm', 'purpose' => 'shopping'],
                ],
            ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testShareJourneyWizard(
        string $journeyFixtureRef,
        string $journeyStartTime,
        bool $expectedHomeDisallowed,
        string $newMidPoint,
        array $expectedOutgoing,
        array $expectedReturn,
        bool $expectedReturnOnNextDay = false,
    ) {
        $this->initialiseClientAndLoadFixtures([SplitJourneyTestFixtures::class]);
        $this->loginUser('interviewer@example.com');

        $entityManager = KernelTestCase::getContainer()->get(EntityManagerInterface::class);
        $diaryKeeperUser = $entityManager
            ->getRepository(User::class)
            ->loadUserByIdentifier('diary-keeper-adult@example.com');

        $this->assertInstanceOf(User::class, $diaryKeeperUser);

        $this->client->request('GET', $this->router->generate('interviewer_dashboard_household', [
            'household' => $diaryKeeperUser->getDiaryKeeper()->getHousehold()->getId(),
        ]));

        $tableHelper = new CrawlerTableHelper($this->client->getCrawler());
        $impersonateUrl = $tableHelper->getLinkUrlForRowMatching('Impersonate', [
            'Name' => 'Test Diary Keeper (Adult)',
        ], false);
        $this->client->request('GET', $impersonateUrl);

        // Now impersonating the diary keeper
        $this->client->request('GET', '/travel-diary/day-1');

        $element = $this->client->getCrawler()->filterXPath('//dt[contains(text(),"'.$journeyStartTime.'")]/following-sibling::dd/following-sibling::dd/a');
        $journeySplitUrl = $element->getElement(0)->getAttribute('href');

        $this->client->request('GET', $journeySplitUrl);

        $this->clickLinkContaining('Split journey');

        $this->context = $this->createContext('');
        $this->performTest(
            $journeyFixtureRef,
            $expectedHomeDisallowed,
            $newMidPoint,
            $expectedOutgoing,
            $expectedReturn,
            $expectedReturnOnNextDay
        );
    }

    protected function getNodeFromDaySummaryPage(Context $context, int $dayNumber, string $xpath): Crawler
    {
        $context->getClient()->request('GET', "/travel-diary/day-{$dayNumber}");
        return $this->getNode($context, $xpath);
    }

    protected function getNode(Context $context, string $xpath): Crawler
    {
        $node = $context->getClient()->getCrawler()->filterXPath($xpath);
        $this->assertThat(
            $node,
            $this->logicalNot($this->logicalOr(
                $this->isNull(),
                $this->countOf(0),
            )),
            "Could not find element matching: $xpath",
        );
        return $node;
    }
}