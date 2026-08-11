<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow\BugTests;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Covers the `is_valid_alternative_start_place` expressions in
 * config/form_wizards/TravelDiary/combined-reflow-wizard.php.
 *
 * They decide whether a request may enter a wizard at a place with no history
 * behind it (AbstractFormWizardController::doWorkflow). Two things can go wrong:
 * they can throw, or they can be too permissive / too strict. This pins both.
 */
class WizardStartPlaceTest extends AbstractFunctionalWebTestCase
{
    /**
     * Regression: the stage expressions dereference state.getStage() unguarded,
     * and isEmpty() is an ordinary function whose argument is evaluated before
     * the call — so it cannot protect the deref inside it.
     * EditJourneyController::getState() only calls reloadJourney(), leaving the
     * stage null, so deep-linking it at a stage place threw
     * "Unable to call method getId of non-object" with no stale page involved.
     */
    public function testStagePlaceDeepLinkOnTheJourneyEditWizardDoesNotThrow(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');

        $journeyId = $this->getDayJourney(1)->getId();

        $this->client->request(
            Request::METHOD_GET,
            "/travel-diary/reflow/journey/{$journeyId}/edit/standard--stage-details"
        );

        self::assertLessThan(
            500,
            $this->client->getResponse()->getStatusCode(),
            'Deep-linking the journey-edit wizard at a stage place must not throw: '
            . 'its getState() never populates a stage.'
        );
    }

    /**
     * The Change links on the journey view are the whole reason
     * is_valid_alternative_start_place exists. Gating the journey-level places on
     * isEditing() must not break them.
     */
    public function testChangeLinkIntoJourneyDetailsStillWorks(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');
        $this->client->followRedirects(true);

        $journeyId = $this->getDayJourney(1)->getId();

        $this->client->request(
            Request::METHOD_GET,
            "/travel-diary/reflow/journey/{$journeyId}/edit/standard--details"
        );

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'standard--details',
            $this->client->getRequest()->getRequestUri(),
            'A Change link into journey-details must still render in the edit wizard.'
        );
    }

    /**
     * standard--stage-method is not the workflow's initial place (wizard-type is),
     * and a fresh add-stage session has no history — so this route enters solely
     * via its is_valid_alternative_start_place expression. It must stay permissive.
     */
    public function testAddStageWizardEntryPointStillWorks(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');
        $this->client->followRedirects(true);

        $journeyId = $this->getDayJourney(1)->getId();

        // The controller sets the place itself rather than redirecting, so the URL
        // stays on /add-stage while rendering the method page.
        $crawler = $this->client->request(Request::METHOD_GET, "/travel-diary/reflow/journey/{$journeyId}/add-stage");

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(
            0,
            $crawler->selectButton('method[button_group][continue]')->count(),
            'The add-stage wizard enters at the method page via its start-place expression.'
        );
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
