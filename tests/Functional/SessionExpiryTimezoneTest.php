<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\DataFixtures\UserFixtures;

class SessionExpiryTimezoneTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        // initialise the web server with a timezone that is far in advance of the client, so that the offset dictates
        // that the session expiry will be shown if timezone is not taken into account
        // since it doesn't seem to be possible to change the client timezone, we need to assume that dev is taking
        // place in Europe/London or similar...
        $this->initialiseClientAndLoadFixtures([UserFixtures::class], ['env' => ['TZ' => 'America/Belize']]);
    }

    public function testSessionExpiryWithDifferentBrowserTimezone()
    {
        // Session expiry element is not included unless a user is logged in
        /** @var User $interviewerUser */
        $interviewerUser = $this->getFixtureByReference('user:interviewer');
        $this->loginUser($interviewerUser->getUserIdentifier(), 'password');

        sleep(2);
        $this->assertSelectorIsNotVisible('#session-reminder .expired');
    }
}