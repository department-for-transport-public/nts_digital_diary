<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\Entity\Embeddable\CostOrNil;
use App\Tests\DataFixtures\JourneyFixtures;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Twig\CostOrNilExtension;
use App\Utility\DateTimeFormats;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardTest extends AbstractFunctionalTestCase
{
    protected CostOrNilExtension $costOrNilExtension;
    protected TranslatorInterface $translator;
    protected DateTimeFormats $dateTimeFormats;

    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            StageFixtures::class
        ]);

        $this->loginUser('diary-keeper-adult@example.com');

        $container = static::getContainer();
        $this->costOrNilExtension = $container->get(CostOrNilExtension::class);
        $this->translator = $container->get(TranslatorInterface::class);
        $this->dateTimeFormats = $container->get(DateTimeFormats::class);
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
                (new \DateTime($definition->getStartTime()))->format($this->dateTimeFormats->getTime(DateTimeFormats::MODE_12_HOUR)),
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
            $journeyTime = (new \DateTime($journeyDefinition->getStartTime()))->format($this->dateTimeFormats->getTime(DateTimeFormats::MODE_12_HOUR));
            $links = $this->client->getCrawler()->filterXPath("//dt[text()='{$journeyTime}']/following-sibling::dd//a")->links();
            $this->assertCount(1, $links);
            $this->client->click($links[0]);

            foreach($journeyDefinition->getStageDefinitions() as $stageDefinition) {
                // Click the relevant tab header
                $showButton = $this->client->getCrawler()->filterXPath("//li[@id='step-nav-stage-{$stageDefinition->getNumber()}']//span[contains(concat(' ', normalize-space(@class), ' '), ' app-step-nav__button-text ')]");
                $this->assertCount(1, $showButton);
                $showButton->getElement(0)->click();

                // Read the data from the summary list
                $data = $this->getSummaryListData("//div[@id='step-panel-stage-{$stageDefinition->getNumber()}']/dl/div");

                $method = $this->translator->trans("stage.method.choices.{$stageDefinition->getMethod()}", [], 'travel-diary');
                $methodOther = $stageDefinition->getMethodOther();

                if ($methodOther) {
                    $method .= ": ". $methodOther;
                }

                $this->assertDataHasKeyValuePair($data,
                    'Transport method',
                    $method
                );

                $distance = $stageDefinition->getDistance();
                $this->assertDataHasKeyValuePair($data,
                    'Distance',
                    $this->translator->trans("distance.{$distance->getUnit()}", ['value' => $distance->getValue()->toFloat()], 'messages')
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
                        $this->costOrNilExtension->format_cost_or_nil((new CostOrNil())->decodeFromSingleValue($ticketCost), 'stage.view.ticket-cost.value', '-')
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
                        $this->costOrNilExtension->format_cost_or_nil((new CostOrNil())->decodeFromSingleValue($parkingCost), 'stage.view.parking-cost.value', '-')
                    );
                }
            }
        }
    }
}
