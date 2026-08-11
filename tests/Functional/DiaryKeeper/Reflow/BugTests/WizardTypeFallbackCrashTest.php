<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow\BugTests;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Regression: reaching the combined-reflow workflow's initial place
 * (STATE_WIZARD_TYPE / "wizard-type") in a wizard that does not carry a day
 * number 500'd.
 *
 * When a request targets a place with no session history behind it and which is
 * not a valid (alternative) start place, AbstractFormWizardController::doWorkflow
 * falls back to the workflow's initial place — here "wizard-type". Rendering that
 * place runs getAdditionalViewData(), which for wizard-type builds the
 * set_journey_title_translation_parameters block and calls getDiaryDay(). That in
 * turn force-creates a DiaryDay from $this->dayNumber via
 * (new DiaryDay())->setNumber($this->dayNumber).
 *
 * The return- and edit- reflow wizards are keyed by journey/stage, not by day, so
 * $this->dayNumber is always null there. setNumber(null) then throws a TypeError:
 *   "App\Entity\DiaryDay::setNumber(): Argument #1 ($number) must be of type int,
 *    null given".
 *
 * The add-journey / add-stage wizards already guard against this via
 * bounceBackRedirectResponse() (see BackButtonAfterAddAnotherTest); the return and
 * edit wizards did not, so the fallback rendered wizard-type and crashed.
 *
 * A sibling case covered here (testBackToTargetDayFirstPage...) reaches a 500 by a
 * related but distinct route: return--target-day is an alternative start place, so
 * a stale request renders it *directly* rather than falling back to wizard-type.
 * The fix there gates its is_valid_alternative_start_place on the source journey so
 * such requests fall through to the same bounce-back guard.
 */
class WizardTypeFallbackCrashTest extends AbstractFunctionalWebTestCase
{
    private const string USERNAME = 'diary-keeper-adult@example.com';

    /**
     * The exact reported scenario. The user completes the return-journey wizard
     * (which lands them on the new journey and calls cleanUp(), wiping the wizard
     * session), navigates away, then presses the browser back button onto a
     * mid-wizard page. With the session gone, doWorkflow falls back to wizard-type
     * and previously 500'd on setNumber(null).
     */
    public function testBrowserBackAfterCompletingReturnJourneyDoesNotCrash(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm(self::USERNAME, 'password');
        $this->client->followRedirects(true);

        // journey:1 (day 1, Home -> Wobble): a single private-car stage, returns home.
        $sourceJourneyId = $this->getDayJourney(1, 0)->getId();
        $base = "/travel-diary/reflow/journey/{$sourceJourneyId}/return-journey";

        // Walk the whole return-journey wizard through to completion.
        $this->client->request(Request::METHOD_GET, $base);
        $this->client->submitForm('target_day[button_group][continue]', [
            'target_day[diaryDay]' => 'day-3',
        ]);
        $this->client->submitForm('journey_details[button_group][continue]', [
            'journey_details[times][startTime][hour]' => '1',
            'journey_details[times][startTime][minute]' => '59',
            'journey_details[times][startTime][am_or_pm]' => 'pm',
            'journey_details[times][endTime][hour]' => '2',
            'journey_details[times][endTime][minute]' => '25',
            'journey_details[times][endTime][am_or_pm]' => 'pm',
            'journey_details[isSameStages]' => 'true',
        ]);
        $this->client->submitForm('stage_details[button_group][continue]', [
            'stage_details[parkingCost][hasCost]' => 'false',
        ]);

        // Wizard finished: we are on the newly created return journey's view.
        self::assertResponseIsSuccessful();
        self::assertMatchesRegularExpression(
            '#^/travel-diary/journey/[0-9A-Z]+$#',
            $this->client->getRequest()->getPathInfo(),
            'Completing the return-journey wizard should land on the new journey view.'
        );

        // Browser back to a stale mid-wizard page. Must not 500.
        $this->client->request(Request::METHOD_GET, "{$base}/return--stage-details/1");

        self::assertResponseIsSuccessful();
        self::assertSame(
            "/travel-diary/journey/{$sourceJourneyId}",
            $this->client->getRequest()->getPathInfo(),
            'Back onto a stale return-wizard page should bounce to the source journey view, not crash on wizard-type.'
        );
    }

    /**
     * return--target-day is the return wizard's first page. It is an alternative
     * start place, so - unlike the mid-wizard places - a stale request renders it
     * directly rather than falling back to wizard-type. Its form queries with the
     * state's source journey, which a stale request has lost, so it 500'd. Gating
     * its is_valid_alternative_start_place on the source journey now routes stale
     * requests through the bounce-back guard instead.
     */
    public function testBackToTargetDayFirstPageOnReturnJourneyWizardDoesNotCrash(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm(self::USERNAME, 'password');
        $this->client->followRedirects(true);

        $journeyId = $this->getDayJourney(1, 0)->getId();

        $this->client->request(
            Request::METHOD_GET,
            "/travel-diary/reflow/journey/{$journeyId}/return-journey/return--target-day"
        );

        self::assertResponseIsSuccessful();
        self::assertSame(
            "/travel-diary/journey/{$journeyId}",
            $this->client->getRequest()->getPathInfo(),
            'A stale request to the return wizard\'s first page should bounce to the source journey, not crash.'
        );
    }

    /**
     * The same crash without needing to complete the wizard first: deep-linking a
     * mid-wizard place on a fresh session has no history either, so doWorkflow
     * takes the same fallback to wizard-type.
     */
    public function testDeepLinkMidWizardPlaceOnReturnJourneyWizardDoesNotCrash(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm(self::USERNAME, 'password');
        $this->client->followRedirects(true);

        $journeyId = $this->getDayJourney(1, 0)->getId();

        $this->client->request(
            Request::METHOD_GET,
            "/travel-diary/reflow/journey/{$journeyId}/return-journey/return--stage-details/1"
        );

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(
            'wizard-type',
            $this->client->getRequest()->getPathInfo(),
            'The return wizard must never render its wizard-type page (it has no day number).'
        );
    }

    /**
     * Theoretical sibling on the journey-edit wizard, which is also day-number-less.
     *
     * NB: WizardStartPlaceTest::testStagePlaceDeepLinkOnTheJourneyEditWizardDoesNotThrow
     * checks the same URL but does NOT follow the redirect, so it only sees the 302
     * to wizard-type and never the crash. Following the redirect exposes it.
     */
    public function testDeepLinkFallbackPlaceOnEditJourneyWizardDoesNotCrash(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm(self::USERNAME, 'password');
        $this->client->followRedirects(true);

        $journeyId = $this->getDayJourney(1, 0)->getId();

        // standard--stage-details is not a valid start place for the journey-edit
        // wizard (its getState never populates a stage), so this falls back to
        // wizard-type.
        $this->client->request(
            Request::METHOD_GET,
            "/travel-diary/reflow/journey/{$journeyId}/edit/standard--stage-details"
        );

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(
            'wizard-type',
            $this->client->getRequest()->getPathInfo(),
            'The journey-edit wizard must never render its wizard-type page (it has no day number).'
        );
    }

    /**
     * Theoretical sibling on the stage-edit wizard, also day-number-less.
     * standard--stage-method is not a valid start place there (the reference stage
     * already has an id, so the isEmpty(id) guard fails), so it falls back to
     * wizard-type.
     */
    public function testDeepLinkFallbackPlaceOnEditStageWizardDoesNotCrash(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm(self::USERNAME, 'password');
        $this->client->followRedirects(true);

        $stageId = $this->getDayJourney(1, 0)->getStages()->first()->getId();

        $this->client->request(
            Request::METHOD_GET,
            "/travel-diary/reflow/stage/{$stageId}/edit/standard--stage-method"
        );

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(
            'wizard-type',
            $this->client->getRequest()->getPathInfo(),
            'The stage-edit wizard must never render its wizard-type page (it has no day number).'
        );
    }

    private function getDayJourney(int $dayNumber, int $index = 0): Journey
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $dk = $em->getRepository(User::class)
            ->getDiaryKeeperJourneysAndStagesForTests(self::USERNAME);

        return $dk->getDiaryDayByNumber($dayNumber)->getJourneys()[$index];
    }
}
