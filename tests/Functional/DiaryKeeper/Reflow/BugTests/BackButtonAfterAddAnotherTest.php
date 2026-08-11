<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow\BugTests;

use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Regression: after answering "No" (add another stage) on the add-another form,
 * the user lands on the stage-method page for the next stage, and entering that
 * page clears the wizard place-history. Pressing the browser back button then
 * issues a GET for the add-another URL, which doWorkflow can no longer resolve
 * from history and falls back to the wizard's initial place (wizard-type).
 *
 * That fallback is not a valid destination once a journey exists:
 *  - the add-journey wizard silently restarted at wizard-type, and
 *  - the add-stage wizard (which has no wizard-type page) returned a 500.
 *
 * bounceBackRedirectResponse() now sends the user to the journey view instead.
 */
class BackButtonAfterAddAnotherTest extends AbstractFunctionalWebTestCase
{
    private const string ADD_JOURNEY_BASE = '/travel-diary/reflow/day-2/new-add-journey';

    /**
     * Journey-details is only a valid alternative start place when editing (that
     * is what the Change links on the journey view do). Once a stage is in
     * progress, going back to it would let the user re-enter the journey-level
     * pages of a flow they are midway through — and the half-built stage sitting
     * on the session journey is what used to wedge the wizard. So the back press
     * bounces to the journey view instead.
     */
    public function testBackFromFirstStageMethodBouncesToJourneyView(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');
        $this->client->followRedirects(true);

        $this->navigateToFirstStageMethod();
        $journeyId = $this->getDayJourney(2)->getId();

        $this->client->request(Request::METHOD_GET, self::ADD_JOURNEY_BASE . '/standard--details');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            "/travel-diary/journey/{$journeyId}",
            $this->client->getRequest()->getRequestUri(),
            'Back from the first stage method should bounce to the journey view.'
        );
    }

    public function testBackFromSecondStageMethodReturnsToAddAnother(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');
        $this->client->followRedirects(true);

        $this->navigateToFirstStageMethod();
        $this->completeFirstStageAndAddAnother();

        // Now on the stage-2 method page.
        self::assertStringContainsString('standard--stage-method', $this->client->getRequest()->getRequestUri());

        // Simulate the browser back button: GET the add-another URL directly. It
        // must return to the add-another page (its genuine previous page), not
        // restart the wizard or redirect away.
        $this->client->request(Request::METHOD_GET, self::ADD_JOURNEY_BASE . '/standard--stage-add-another');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('standard--stage-add-another', $this->client->getRequest()->getRequestUri());

        // The review lists the completed stage; a trailing in-progress "this
        // stage" row is also shown (a known cosmetic — the not-yet-defined next
        // stage). The important guarantee is that no phantom stage is persisted.
        self::assertCount(1, $this->getDayJourney(2)->getStages());
    }

    public function testBackFromSecondStageMethodInAddStageWizardReturnsToAddAnother(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');

        $journey = $this->getDayJourney(1);
        $journeyId = $journey->getId();
        $persistedStageCount = $journey->getStages()->count();
        $base = "/travel-diary/reflow/journey/{$journeyId}/add-stage";

        $this->client->followRedirects(true);
        $this->client->request(Request::METHOD_GET, $base);
        $this->submitStageMethodDetailsAndAddAnother();

        self::assertStringContainsString('standard--stage-method', $this->client->getRequest()->getRequestUri());

        // Browser back to add-another. Previously this 500'd (no wizard-type page
        // in the add-stage wizard); it must now return to the add-another page.
        $this->client->request(Request::METHOD_GET, "{$base}/standard--stage-add-another");

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('standard--stage-add-another', $this->client->getRequest()->getRequestUri());

        // Only the stage added during this flow persisted (original + 1); the
        // review also shows a trailing in-progress "this stage" row (known
        // cosmetic), but nothing extra is written to the database.
        self::assertCount($persistedStageCount + 1, $this->getDayJourney(1)->getStages());
    }

    private function navigateToFirstStageMethod(): void
    {
        $this->client->request(Request::METHOD_GET, self::ADD_JOURNEY_BASE);
        $this->client->submitForm('wizard_type[button_group][continue]', [
            'wizard_type[wizardType]' => 'add',
        ]);
        $this->client->submitForm('locations[button_group][continue]', [
            'locations[start_choice]' => 'home',
            'locations[end_choice]' => 'other',
            'locations[endLocation]' => 'Testington factory',
        ]);
        $this->client->submitForm('journey_details[button_group][continue]', [
            'journey_details[purpose][purpose]' => 'Multi-stage journey',
            'journey_details[times][startTime][hour]' => '9',
            'journey_details[times][startTime][minute]' => '00',
            'journey_details[times][startTime][am_or_pm]' => 'am',
            'journey_details[times][endTime][hour]' => '10',
            'journey_details[times][endTime][minute]' => '00',
            'journey_details[times][endTime][am_or_pm]' => 'am',
        ]);
    }

    private function completeFirstStageAndAddAnother(): void
    {
        $this->client->submitForm('method[button_group][continue]', ['method[method]' => '1']);
        $this->client->submitForm('details[button_group][continue]', [
            'details[distanceTravelled][value]' => '10',
            'details[distanceTravelled][unit]' => 'miles',
            'details[travelTime]' => '20',
            'details[companions][adultCount]' => '1',
            'details[companions][childCount]' => '0',
        ]);
        // is_final_stage=false => "No, add another stage".
        $this->client->submitForm('add_another[button_group][continue]', [
            'add_another[is_final_stage]' => 'false',
        ]);
    }

    private function submitStageMethodDetailsAndAddAnother(): void
    {
        $this->client->submitForm('method[button_group][continue]', ['method[method]' => '1']);
        $this->client->submitForm('details[button_group][continue]', [
            'details[distanceTravelled][value]' => '10',
            'details[distanceTravelled][unit]' => 'miles',
            'details[travelTime]' => '20',
            'details[companions][adultCount]' => '1',
            'details[companions][childCount]' => '0',
        ]);
        $this->client->submitForm('add_another[button_group][continue]', [
            'add_another[is_final_stage]' => 'false',
        ]);
    }

    private function getDayJourney(int $dayNumber): \App\Entity\Journey\Journey
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $dk = $em->getRepository(User::class)
            ->getDiaryKeeperJourneysAndStagesForTests('diary-keeper-adult@example.com');

        return $dk->getDiaryDayByNumber($dayNumber)->getJourneys()[0];
    }
}
