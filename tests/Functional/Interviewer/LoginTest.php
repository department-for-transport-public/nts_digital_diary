<?php

namespace App\Tests\Functional\Interviewer;

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

    public function testEmptyForm(): void
    {
        $this->loginUser('', '');
        $this->assertSelectorExists('#user_login_group-error');
    }

    public function testIncorrectCredentials(): void
    {
        $this->loginUser('interviewer@example.com', 'wr0ngP4ssword');
        $this->assertSelectorExists('#user_login_group-error');
    }

    public function testCorrectCredentials(): void
    {
        $this->loginUser('interviewer@example.com', 'password');
        $this->assertEquals('/interviewer', $this->getUrlPath());
    }
}
