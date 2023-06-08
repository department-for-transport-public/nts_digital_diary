<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\DataFixtures\TestSpecific\DiaryKeeperForDiaryAccessFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use function PHPUnit\Framework\assertEquals;

class DiaryAccessTest extends AbstractFunctionalWebTestCase
{
    private DiaryKeeper $patsyDiaryKeeper;

    private const IGNORED_ROUTES = [
        'traveldiary_return_journey_wizard_start',
        'traveldiary_share_journey_wizard_start',
        'traveldiary_stage_wizard_start',
    ];

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class, DiaryKeeperForDiaryAccessFixtures::class]);

        $loggedInDiaryKeeper = $this->getDiaryKeeper('diary-keeper:access');
        $this->patsyDiaryKeeper = $this->getDiaryKeeper('diary-keeper:adult');

        $this->submitLoginForm($loggedInDiaryKeeper->getUser()->getUserIdentifier(), 'access');
    }

    public function dataAccess(): array
    {
        return [
            'View journey' => ["/journey/{journeyId}"],
            'Delete journey' => ["/journey/{journeyId}/delete"],
            'Complete journey' => ["/journey/{journeyId}/complete"],
            'Edit journey' => ["/journey/{journeyId}/edit"],
            'Return journey' => ["/journey/{journeyId}/return-journey/introduction"],
            'Share journey' => ["/journey/{journeyId}/share-journey/introduction"],
            'Re-order stages' => ["/journey/{journeyId}/reorder-stages"],
            'Add stage' => ["/journey/{journeyId}/add-stage/method"],
            'Delete stage' => ["/stages/{stageId}/delete"],
            'Edit stage' => ["/stage/{stageId}/edit/details"],
            'Edit vehicle' => ["/vehicle/{vehicleId}"],
        ];
    }


    /**
     * Enumerate all routes containing journey, stage or vehicle parameters and ensure that our test data
     * contains all of them
     */
    public function testData()
    {
        $data = array_map(fn($a) => $a[0], $this->dataAccess());

        /** @var RouterInterface $router */
        $router = $this->getContainer()->get(RouterInterface::class);

        $relevantRoutes = array_filter($router->getRouteCollection()->all(), function(Route $route, string $name) {
            if (in_array($name, self::IGNORED_ROUTES)) return false;
            if (!str_starts_with($name, "traveldiary_")) return false;
            if (!preg_match('/\{(journey(Id)?|stage(Id)?|vehicle(Id)?)}/', $route->getPath())) return false;
            return true;
        }, ARRAY_FILTER_USE_BOTH);

        // needed to reset the router context
        $this->client->request('GET', '/');

        foreach ($data as $url) {
            $route = $router->match("/travel-diary{$this->replaceUrlParts($url)}");
            self::assertArrayHasKey($route['_route'], $relevantRoutes);
            unset($relevantRoutes[$route['_route']]);
        }

        self::assertCount(0, $relevantRoutes);
    }

    /**
     * @dataProvider dataAccess
     */
    public function testAccess($url)
    {
        $this->client->request('GET', "/travel-diary{$this->replaceUrlParts($url)}");
        assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    protected function findJourneyForDiaryKeeper(DiaryKeeper $diaryKeeper): ?Journey
    {
        foreach ($diaryKeeper->getDiaryDays() as $day) {
            foreach ($day->getJourneys() as $journey) {
                if ($journey->getStages()->count() > 1) {
                    return $journey;
                }
            }
        }
        return null;
    }

    protected function replaceUrlParts(string $url): string
    {
        $patsyJourney = $this->findJourneyForDiaryKeeper($this->patsyDiaryKeeper);

        return str_replace(
            ['{journeyId}', '{stageId}', '{vehicleId}'],
            [$patsyJourney->getId(), $patsyJourney->getStages()->first()->getId(), $this->patsyDiaryKeeper->getPrimaryDriverVehicles()->first()->getId()],
            $url
        );
    }

    protected function getDiaryKeeper($ref): DiaryKeeper
    {
        /** @var DiaryKeeper $dk */
        $dk = $this->getFixtureByReference($ref);
        return $dk;
    }
}