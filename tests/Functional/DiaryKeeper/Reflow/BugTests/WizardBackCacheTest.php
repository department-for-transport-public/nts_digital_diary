<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow\BugTests;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Repro: bounceBackRedirectResponse() catches a user who has pressed the browser
 * back button past the start of a new stage and sends them to the journey view.
 * Server-side that works (see testSecondBackPressBouncesToJourneyView), but in a
 * real browser it never runs.
 *
 * The wizard's responses carry Symfony's default for a session-backed page,
 * `Cache-Control: max-age=0, must-revalidate, private`, with no `no-store`. Those
 * directives govern the HTTP cache; the back/forward cache is a separate
 * mechanism that restores the whole page from memory without issuing a request
 * at all, and only `no-store` opts out of it. Nor does the POST-redirect-GET
 * flow help: every transition ends in a 302 (AbstractFormWizardController::
 * applyTransitionAndRedirect), so every history entry is a plain GET and there is
 * no form resubmission for the browser to re-evaluate.
 *
 * The user therefore sees the original page, and the form on it is stale — see
 * PhantomStageSessionPoisonTest for what happens when they then press Continue.
 */
class WizardBackCacheTest extends AbstractFunctionalWebTestCase
{
    private const string ADD_JOURNEY_BASE = '/travel-diary/reflow/day-2/new-add-journey';

    /**
     * Guards the behaviour that already works. Once a new stage has started the
     * history holds only the add-another place, so the first back press resolves
     * to add-another and empties the history. A second back press then has no
     * valid landing place and must bounce to the journey view, rather than
     * falling back to the wizard's initial place (wizard-type).
     */
    public function testSecondBackPressBouncesToJourneyView(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');
        $this->client->followRedirects(true);

        $this->navigateToStageTwoMethod();
        $journeyId = $this->getDayJourney(2)->getId();

        // Back press 1: add-another is still in history, so it renders.
        $this->client->request(Request::METHOD_GET, self::ADD_JOURNEY_BASE . '/standard--stage-add-another');
        self::assertResponseIsSuccessful();

        // Back press 2: nothing valid left in history -> bounce to the journey view.
        $this->client->request(Request::METHOD_GET, self::ADD_JOURNEY_BASE . '/standard--stage-details');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            "/travel-diary/journey/{$journeyId}",
            $this->client->getRequest()->getRequestUri(),
            'A back press with no valid history place must bounce to the journey view.'
        );
    }

    private function navigateToStageTwoMethod(): void
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
