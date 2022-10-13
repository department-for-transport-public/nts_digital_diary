<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;

class LoginTest extends AbstractFunctionalTestCase
{
    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            UserFixtures::class
        ]);
    }

    // N.B. Empty form tested by Interviewer/LoginTest

    public function testIncorrectCredentials(): void
    {
        $this->loginUser('diary-keeper-adult@example.com', 'wr0ngP4ssword');
        $this->assertSelectorExists('#user_login_group-error');
    }

    public function testCorrectCredentials(): void
    {
        $this->loginUser('diary-keeper-adult@example.com', 'password');
        $this->assertEquals('/travel-diary', $this->getUrlPath());
    }
}
