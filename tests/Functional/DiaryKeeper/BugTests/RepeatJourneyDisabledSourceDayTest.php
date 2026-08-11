<?php

namespace App\Tests\Functional\DiaryKeeper\BugTests;

use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Symfony\Component\HttpFoundation\Request;

class RepeatJourneyDisabledSourceDayTest extends AbstractFunctionalWebTestCase
{
    /**
     * Days 2-6 have no journeys (fixtures only populate days 1 and 7) and should
     * be rendered as disabled radios on the "select source day" step of the repeat
     * journey wizard. This test confirms that an attacker cannot bypass the
     * HTML-level disable by submitting one of those day ids directly.
     */
    public function testCannotSubmitSourceDayWithNoJourneys(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm('diary-keeper-adult@example.com', 'password');

        $this->client->followRedirects(true);

        // Start the repeat-journey wizard (target day 3 has no journeys yet)
        $this->client->request(Request::METHOD_GET, '/travel-diary/day-3/repeat-journey');
        $this->client->submitForm('form_button_group_continue');
        $this->assertStringContainsString(
            '/repeat-journey/select-source-day',
            $this->client->getRequest()->getRequestUri()
        );

        // Grab the form (which carries a valid CSRF token), then inject a
        // disabled day id to simulate an attacker modifying the HTML or
        // crafting a direct POST
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('source_day_button_group_continue')->form();

        // Use setValues bypass: disabled fields aren't in the ChoiceFormField
        // options list, so we have to set the value through the raw values
        // array rather than via the ChoiceFormField
        $values = $form->getPhpValues();
        $values['source_day']['sourceDayId'] = 'day-2';

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $values,
        );

        // If the disable were only at the HTML layer (the bug), Symfony would
        // bind day-2 to sourceDayId and advance to the select-source-journey
        // step. With the fix, the disabled choice is ignored at the binding
        // layer and we stay on select-source-day (form is invalid because
        // sourceDayId is null).
        $this->assertStringNotContainsString(
            '/repeat-journey/select-source-journey',
            $this->client->getRequest()->getRequestUri(),
            'Submitting a day with no journeys should not advance the wizard'
        );
    }
}
