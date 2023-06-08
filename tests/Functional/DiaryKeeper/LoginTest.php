<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\User;
use App\Tests\DataFixtures\LoginTestFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use ReflectionProperty;

class LoginTest extends AbstractFunctionalTestCase
{
    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            LoginTestFixtures::class
        ]);
    }

    // N.B. Empty form tested by Interviewer/LoginTest

    public function testIncorrectCredentials(): void
    {
        $this->checkLogin('user:diary-keeper:adult', 'wr0ngP4ssword', false);
    }

    public function testCorrectCredentials(): void
    {
        $this->checkLogin('user:diary-keeper:adult', 'password', true);
    }

    public function testProxiedNoLogin(): void
    {
        $this->checkLogin('user:diary-keeper:proxied-no-login', 'password', false);
    }

    public function testInterviewerTrainingNoLogin(): void
    {
        $this->checkLogin('user:diary-keeper:interviewer-training', 'password', false);
    }

    protected function checkLogin(string $userReference, string $password, bool $expectLoginSuccess)
    {
        /** @var User $user */
        $user = $this->getFixtureByReference($userReference);
        // we're given a proxy, so we need to get entity manager to give us the real thing in order to use reflection
        $user->getUserIdentifier();

        $rp = new ReflectionProperty(User::class, 'username');
        $rp->setAccessible(true);
        $this->loginUser($rp->getValue($user), $password);
        if ($expectLoginSuccess) {
            $this->assertEquals('/travel-diary', $this->getUrlPath());
        } else {
            $this->assertSelectorExists('#user_login_group-error');
        }
    }
}
