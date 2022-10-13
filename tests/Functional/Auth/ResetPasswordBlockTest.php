<?php

namespace App\Tests\Functional\Auth;

class ResetPasswordBlockTest extends AbstractAuthTestCase
{
    public function testPasswordReset(): void
    {
        $userOneEmail = 'diary-keeper-adult@example.com';
        $userTwoEmail = 'diary-keeper-child@example.com';

        // Do a forgotten password request
        $this->client->request('GET', '/');
        $this->client->clickLink('Sign in');
        $this->submitPasswordResetForm($userOneEmail);

        // Now log in as another user
        $this->client->request('GET', '/');
        $this->submitLoginForm($userTwoEmail, 'password');

        // Follow the password reset link and verify that the reset form is *not* present on the page
        $passwordResetUrl = $this->getPasswordResetUrlFromMessage();
        $this->client->request('GET', $passwordResetUrl);
        $this->assertFalse($this->isPasswordResetButtonPresent(), 'Password reset button should NOT be preset whilst logged in as another user');

        // Click the logout link
        $this->client->clickLink('Logout');

        // Follow the password reset link and verify that the reset form is now visible
        $this->client->request('GET', $passwordResetUrl);
        $this->assertTrue($this->isPasswordResetButtonPresent(), 'Password reset button should be present, now that we are logged out');
    }

    /**
     * @return bool
     */
    protected function isPasswordResetButtonPresent(): bool
    {
        return $this->client->getCrawler()->selectButton('Reset password')->count() > 0;
    }
}
