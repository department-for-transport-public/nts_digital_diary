<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\Tests\DataFixtures\JourneyFixtures;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Utility\DateTimeFormats;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardTest extends AbstractFunctionalTestCase
{
    protected TranslatorInterface $translator;

    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            StageFixtures::class
        ]);

        $this->loginUser('diary-keeper-adult@example.com');

        $container = self::$kernel->getContainer()->get('test.service_container');
        $this->translator = $container->get(TranslatorInterface::class);
    }

    public function testDashboard(): void
    {
        $this->client->request('GET', '/travel-diary/');

        $data = $this->getSummaryListData();

        $this->assertDataHasKeyValuePair($data, 'Name', 'Test Diary Keeper (Adult)');
        $this->assertDataHasKeyValuePair($data, 'Serial number', '111984 / 08 / 2 / 1');
        $this->assertDataHasKeyValuePair($data, 'Diary status', 'In progress');

        // 3 rows as per above, and 7 rows for days
        $this->assertDataSize($data, 10);
    }

    public function testDashboardBreadcrumbs(): void
    {
        $this->doBreadcrumbsTestStartingAt('/travel-diary/', 2);
    }

    public function testDashboardBackLinks(): void
    {
        $this->doBackLinksTestStartingAt('/travel-diary/', 2);
    }

    public function testJourneysShownOnJourneysScreen(): void
    {
        $this->client->request('GET', '/travel-diary/day-1/');

        $journeyDefinitions = JourneyFixtures::getJourneyDefinitions();

        $data = $this->getSummaryListData();

        $this->assertSameSize($journeyDefinitions, $data);

        foreach(array_values($journeyDefinitions) as $idx => $definition) {
            $rowData = $data[$idx];

            $this->assertEquals(
                (new \DateTime($definition->getStartTime()))->format(DateTimeFormats::TIME_SHORT),
                $rowData[0],
            );

            $this->assertEquals(
                $this->translator->trans('day.journey.description', [
                    'startLocation' => $definition->getStartLocation(),
                    'endLocation' => $definition->getEndLocation(),
                    'stageCount' => count($definition->getStageDefinitions()),
                ], 'travel-diary'),
                $rowData[1],
            );
        }
    }

    public function testStagesShownOnStageScreen(): void
    {
        $journeyDefinitions = JourneyFixtures::getJourneyDefinitions();

        foreach($journeyDefinitions as $journeyDefinition) {
            $this->client->request('GET', '/travel-diary/day-1/');

            // Find the relevant link for the given journeyDefinition and click it...
            $journeyTime = (new \DateTime($journeyDefinition->getStartTime()))->format(DateTimeFormats::TIME_SHORT);
            $links = $this->client->getCrawler()->filterXPath("//dt[text()='{$journeyTime}']/following-sibling::dd//a")->links();
            $this->assertCount(1, $links);
            $this->client->click($links[0]);

            foreach($journeyDefinition->getStageDefinitions() as $stageDefinition) {
                // Click the relevant tab header
                $links = $this->client->getCrawler()->filterXPath("//a[@href='#stage-{$stageDefinition->getNumber()}']")->links();
                $this->assertCount(1, $links);
                $this->client->click($links[0]);

                // Read the data from the summary list
                $data = $this->getSummaryListData("//*[@id='stage-{$stageDefinition->getNumber()}']/dl/div");

                $this->assertDataHasKeyValuePair($data,
                    'Transport method',
                    $this->translator->trans("stage.method.choices.{$stageDefinition->getMethod()}", [], 'travel-diary')
                );

                $distance = $stageDefinition->getDistance();
                $this->assertDataHasKeyValuePair($data,
                    'Distance',
                    $this->translator->trans("distance.{$distance->getUnit()}", ['value' => $distance->getValue()], 'messages')
                );

                $this->assertDataHasKeyValuePair($data,
                    'Time spent travelling',
                    $this->translator->trans("stage.view.travel-time.value", ['minutes' => $stageDefinition->getTravelTime()], 'travel-diary')
                );

                $this->assertDataHasKeyValuePair($data,
                    'Number of people travelling',
                    $this->translator->trans("stage.view.companion-count.value", [
                        'adultCount' => $stageDefinition->getAdultCount(),
                        'childCount' => $stageDefinition->getChildCount(),
                        'count' => $stageDefinition->getAdultCount() + $stageDefinition->getChildCount(),
                    ], 'travel-diary')
                );

                if ($stageDefinition instanceof PublicStageDefinition) {
                    $this->assertDataHasKeyValuePair($data,
                        'Ticket type',
                        $stageDefinition->getTicketType()
                    );

                    $ticketCost = $stageDefinition->getTicketCost();
                    $this->assertDataHasKeyValuePair($data,
                        'Ticket cost',
                        !is_null($ticketCost) ? $this->translator->trans("stage.view.ticket-cost.value", ['cost' => $ticketCost / 100], 'travel-diary') : '-'
                    );

                    $this->assertDataHasKeyValuePair($data,
                        'How many times did you board?',
                        $stageDefinition->getBoardingCount(),
                    );
                } else if ($stageDefinition instanceof PrivateStageDefinition) {
                    $this->assertDataHasKeyValuePair($data, 'Vehicle', $stageDefinition->getVehicle());

                    $this->assertDataHasKeyValuePair($data,
                        'Driver or passenger?',
                        $stageDefinition->getIsDriver() ? 'Driver' : 'Passenger',
                    );

                    $parkingCost = $stageDefinition->getParkingCost();
                    $this->assertDataHasKeyValuePair($data,
                        'Parking cost',
                        !is_null($parkingCost) ? $this->translator->trans('stage.view.parking-cost.value', ['cost' => $parkingCost / 100], 'travel-diary') : '-'
                    );
                }
            }
        }
    }
}
