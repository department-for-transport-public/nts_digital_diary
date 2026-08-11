<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow\BugTests;

use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Repro: opening the add-stage wizard for an existing journey (View journey ->
 * "Add a stage") lands on the stage-method page. The journey review sidebar
 * should list the journey's real stages plus exactly ONE in-progress ("this
 * stage") entry. Bug: it shows several phantom in-progress entries because
 * getState() is called multiple times per render and each fresh call appends a
 * new Stage to the managed journey.
 */
class AddStagePhantomStagesTest extends AbstractFunctionalWebTestCase
{
    public function testAddStageMethodPageShowsSingleInProgressStage(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $dk = $em->getRepository(User::class)
            ->getDiaryKeeperJourneysAndStagesForTests('diary-keeper-adult@example.com');

        // Find a journey to add a stage to, and record its persisted stage count.
        $journey = $dk->getDiaryDayByNumber(1)->getJourneys()[0];
        $journeyId = $journey->getId();
        $persistedStageCount = $journey->getStages()->count();

        $crawler = $this->client->request(
            Request::METHOD_GET,
            "/travel-diary/reflow/journey/{$journeyId}/add-stage"
        );

        self::assertResponseIsSuccessful();

        // The sidebar renders one <li> per stage in journey.stages.
        $renderedStageRows = $crawler->filter('ul.nts-stagelist li')->count();

        self::assertSame(
            $persistedStageCount + 1,
            $renderedStageRows,
            "Expected {$persistedStageCount} persisted stage(s) + 1 in-progress stage, "
            . "but the sidebar rendered {$renderedStageRows} rows."
        );

        // The persisted stage count in the DB must be unchanged by merely viewing
        // the page (no phantom stages leaked into persistence).
        $em->clear();
        $dkReloaded = $em->getRepository(User::class)
            ->getDiaryKeeperJourneysAndStagesForTests('diary-keeper-adult@example.com');
        $journeyReloaded = $dkReloaded->getDiaryDayByNumber(1)->getJourneys()[0];
        self::assertSame(
            $persistedStageCount,
            $journeyReloaded->getStages()->count(),
            'Viewing the add-stage page must not persist any extra stages.'
        );
    }
}
