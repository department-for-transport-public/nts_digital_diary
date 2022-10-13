<?php

namespace App\Tests\Functional\OnBoarding;

class LoginTest extends AbstractOtpTest
{
    private const FIRST_PASSCODE = '1234567890';

    public function testEmptyForm(): void
    {
        $this->loginOtpUser('', '');
        $this->assertSelectorExists('#otp_login_group-error');
    }

    public function testIncorrectCredentials(): void
    {
        $this->loginOtpUser(self::FIRST_PASSCODE, '1111122222');
        $this->assertSelectorExists('#otp_login_group-error');
    }

    public function testCorrectCredentials(): void
    {
        $password = $this->passcodeGenerator->getPasswordForUserIdentifier(self::FIRST_PASSCODE);

        $this->loginOtpUser(self::FIRST_PASSCODE, $password);
        $this->assertEquals('/onboarding/household/introduction', $this->getUrlPath());
    }
}
