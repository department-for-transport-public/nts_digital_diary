<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow\BugTests;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Repro: while a new stage is in progress, getState() -> reloadStage() calls
 * createNewStage(), which appends an unpersisted, method-less Stage to the
 * journey's stages collection. The journey in the database has one fewer stage
 * than the journey held in the session.
 *
 * The journey-details place declares `is_valid_alternative_start_place` as
 * '!isEmpty(state.getSubject().getId())' (combined-reflow-wizard.php), which is
 * true for *any* saved journey — including one that is midway through adding
 * stages. AbstractFormWizardController::doWorkflow checks that before falling
 * back to the initial place, so bounceBackRedirectResponse() never sees these
 * requests and the page renders, rewriting the session place to
 * standard--details.
 *
 * That flips getState() out of the reloadStage() branch and into reloadJourney(),
 * whose mergeCollection() call then walks the session journey's stages against
 * the database journey's, hits the phantom stage with no counterpart, and throws
 * `Cannot use "::class" on null` from PropertyMerger. Every subsequent request to
 * the wizard throws the same way: the session is wedged, not just one page.
 *
 * These are reachable from the browser's back/forward cache restoring a stale
 * page (see WizardBackCacheTest), but the bug is not caused by caching — any
 * route to journey-details while a stage is in progress triggers it.
 */
class PhantomStageSessionPoisonTest extends AbstractFunctionalWebTestCase
{
    private const string ADD_JOURNEY_BASE = '/travel-diary/reflow/day-2/new-add-journey';

    private ?Form $staleJourneyDetailsForm = null;
    private ?Form $staleStageDetailsForm = null;

    /**
     * The benign case, guarding against regression: a stale stage-details submit
     * is rejected on the history check and redirected, without a 500.
     */
    public function testStaleStageDetailsSubmitIsRedirected(): void
    {
        $this->navigateToStageTwoMethod();

        $this->client->followRedirects(false);
        $this->client->submit($this->staleStageDetailsForm);

        self::assertResponseRedirects();
        self::assertStringContainsString(
            'standard--stage-add-another',
            $this->client->getResponse()->headers->get('Location'),
        );
    }

    /**
     * Visiting journey-details while a stage is in progress must not leave the
     * wizard unusable.
     */
    public function testJourneyDetailsDuringStageFlowDoesNotWedgeTheSession(): void
    {
        $this->navigateToStageTwoMethod();

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, self::ADD_JOURNEY_BASE . '/standard--details');
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());

        // Whatever the wizard decided to do above, it must still be usable.
        $this->client->request(Request::METHOD_GET, self::ADD_JOURNEY_BASE . '/standard--stage-add-another');

        self::assertLessThan(
            500,
            $this->client->getResponse()->getStatusCode(),
            'Visiting journey-details mid-stage poisons the session: the phantom stage in '
            . 'the session journey has no counterpart in the persisted journey, so every '
            . 'later request throws in reloadJourney() -> PropertyMerger::mergeCollection().'
        );
    }

    /**
     * Submitting a stale journey-details form fires standard__details__stage_method,
     * which carries `persist => true` and cascades an insert of the method-less
     * phantom stage, violating the stage.method_id NOT NULL constraint.
     * handleUniqueConstraintViolation() only catches unique violations, so this
     * escapes as a 500.
     */
    public function testStaleJourneyDetailsSubmitDoesNotPersistMethodlessStage(): void
    {
        $this->navigateToStageTwoMethod();

        $this->client->followRedirects(false);
        $this->client->submit($this->staleJourneyDetailsForm);

        self::assertLessThan(
            500,
            $this->client->getResponse()->getStatusCode(),
            'A stale journey-details submit cascades an insert of the method-less phantom '
            . 'stage: SQLSTATE[23000] NOT NULL constraint failed: stage.method_id.'
        );

        foreach ($this->getDayJourney(2)->getStages() as $stage) {
            self::assertNotNull(
                $stage->getMethod(),
                'A stage without a method must never reach the database.'
            );
        }
    }

    /**
     * Walks to the stage-2 method page, keeping hold of the journey-details and
     * stage-details forms exactly as the browser still has them in its back/forward
     * cache, so they can be submitted later as a real stale submit would be.
     */
    private function navigateToStageTwoMethod(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');
        $this->client->followRedirects(true);

        $this->client->request(Request::METHOD_GET, self::ADD_JOURNEY_BASE);
        $this->client->submitForm('wizard_type[button_group][continue]', [
            'wizard_type[wizardType]' => 'add',
        ]);
        $this->client->submitForm('locations[button_group][continue]', [
            'locations[start_choice]' => 'home',
            'locations[end_choice]' => 'other',
            'locations[endLocation]' => 'Testington factory',
        ]);

        $this->staleJourneyDetailsForm = $this->client->getCrawler()
            ->selectButton('journey_details[button_group][continue]')
            ->form([
                'journey_details[purpose][purpose]' => 'Multi-stage journey',
                'journey_details[times][startTime][hour]' => '9',
                'journey_details[times][startTime][minute]' => '00',
                'journey_details[times][startTime][am_or_pm]' => 'am',
                'journey_details[times][endTime][hour]' => '10',
                'journey_details[times][endTime][minute]' => '00',
                'journey_details[times][endTime][am_or_pm]' => 'am',
            ]);
        $this->client->submit($this->staleJourneyDetailsForm);

        $this->client->submitForm('method[button_group][continue]', ['method[method]' => '1']);

        $this->staleStageDetailsForm = $this->client->getCrawler()
            ->selectButton('details[button_group][continue]')
            ->form([
                'details[distanceTravelled][value]' => '10',
                'details[distanceTravelled][unit]' => 'miles',
                'details[travelTime]' => '20',
                'details[companions][adultCount]' => '1',
                'details[companions][childCount]' => '0',
            ]);
        $this->client->submit($this->staleStageDetailsForm);

        // is_final_stage=false => "No, add another stage".
        $this->client->submitForm('add_another[button_group][continue]', [
            'add_another[is_final_stage]' => 'false',
        ]);

        self::assertStringContainsString('standard--stage-method', $this->client->getRequest()->getRequestUri());
    }

    private function getDayJourney(int $dayNumber): Journey
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $dk = $em->getRepository(User::class)
            ->getDiaryKeeperJourneysAndStagesForTests('diary-keeper-adult@example.com');

        return $dk->getDiaryDayByNumber($dayNumber)->getJourneys()[0];
    }
}
