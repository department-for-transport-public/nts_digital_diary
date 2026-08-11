<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\DataFixtures\TestSpecific\DiaryKeeperForDiaryAccessFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use function PHPUnit\Framework\assertEquals;

class DiaryAccessTest extends AbstractFunctionalWebTestCase
{
    private DiaryKeeper $patsyDiaryKeeper;

    private const array IGNORED_ROUTES = [
        'traveldiary_journey_view_example',
        'traveldiary_share_journey_wizard_start',

        // Split journey is interviewer-only (uses impersonator_is_granted). Access control for split
        // journey is tested by SplitJourneyVisibilityTest, which covers both DK and interviewer roles
        // across multiple journey configurations.
        'traveldiary_split_journey_wizard_start',
        'traveldiary_split_journey_wizard_place',

        // _start routes redirect to the first wizard _place (which IS tested via dataAccess)
        'traveldiary_reflow_add_stage_wizard_start',
        'traveldiary_reflow_return_journey_wizard_start',
        'traveldiary_stage_wizard_start',
        'traveldiary_return_journey_wizard_start',
    ];

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class, DiaryKeeperForDiaryAccessFixtures::class]);

        $loggedInDiaryKeeper = $this->fixtureReferenceRepository->getReference('diary-keeper:access', DiaryKeeper::class);
        $this->patsyDiaryKeeper = $this->fixtureReferenceRepository->getReference('diary-keeper:adult', DiaryKeeper::class);

        $this->submitLoginForm($loggedInDiaryKeeper->getUser()->getUserIdentifier(), 'access');
    }

    public function dataAccess(): array
    {
        return [
            'View journey' => ["/journey/{journeyId}"],
            'Delete journey' => ["/journey/{journeyId}/delete"],
            'Edit journey (reflow)' => ["/reflow/journey/{journeyId}/edit/standard--locations"],
            'Edit journey' => ["/journey/{journeyId}/edit/locations"],
            'Return journey (reflow)' => ["/reflow/journey/{journeyId}/return-journey/return--target-day"],
            'Return journey' => ["/journey/{journeyId}/return-journey/introduction"],
            'Share journey' => ["/journey/{journeyId}/share-journey/introduction"],
            'Re-order stages' => ["/journey/{journeyId}/reorder-stages"],
            'Add stage (reflow)' => ["/reflow/journey/{journeyId}/add-stage/method"],
            'Add stage' => ["/journey/{journeyId}/add-stage/method"],
            'Delete stage' => ["/stages/{stageId}/delete"],
            'Edit stage (reflow)' => ["/reflow/stage/{stageId}/edit/details"],
            'Edit stage' => ["/stage/{stageId}/edit/method"],
            'Edit vehicle' => ["/vehicle/{vehicleId}"],
        ];
    }


    /**
     * Enumerate all routes containing journey, stage or vehicle parameters and ensure that our test data
     * contains all of them.
     *
     * The data in dataAccess is used in testAccess to check whether a user can mess with URL parts and
     * access another users data. This test is used to check whether the test data is comprehensive, and
     * covering all relevant travel diary routes.
     */
    public function testData(): void
    {
        $data = array_map(fn($a) => $a[0], $this->dataAccess());

        /** @var RouterInterface $router */
        $router = $this->getContainer()->get(RouterInterface::class);

        $relevantRoutes = array_filter($router->getRouteCollection()->all(), function(Route $route, string $name): bool {
            if (in_array($name, self::IGNORED_ROUTES)) return false;
            if (!str_starts_with($name, "traveldiary_")) return false;
            if (!preg_match('/\{(journey(Id)?|stage(Id)?|vehicle(Id)?)}/', $route->getPath())) return false;
            return true;
        }, ARRAY_FILTER_USE_BOTH);

        // needed to reset the router context
        $this->client->request(Request::METHOD_GET, '/');

        foreach ($data as $url) {
            $route = $router->match("/travel-diary{$this->replaceUrlParts($url)}");
            self::assertArrayHasKey($route['_route'], $relevantRoutes);
            unset($relevantRoutes[$route['_route']]);
        }

        self::assertCount(0, $relevantRoutes, json_encode($relevantRoutes));
    }

    /**
     * @dataProvider dataAccess
     */
    public function testAccess(string $url): void
    {
        $this->client->request(Request::METHOD_GET, "/travel-diary{$this->replaceUrlParts($url)}");
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
}
