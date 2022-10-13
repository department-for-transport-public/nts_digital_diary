<?php

namespace App\Tests\Functional\Auth;

class ResetPasswordTest extends AbstractAuthTestCase
{
    public function testPasswordReset(): void
    {
        $emailAddress = 'diary-keeper-adult@example.com';

        $this->client->request('GET', '/');

        // Check that we can log in with the known password...
        $this->submitLoginForm($emailAddress, 'password');
        $this->assertEquals('/travel-diary', $this->client->getRequest()->getRequestUri());
        $this->client->clickLink('Logout');
        $this->assertEquals('/', $this->client->getRequest()->getRequestUri());
        $this->client->clickLink('Sign in');

        // Submit the password reset form...
        $this->submitPasswordResetForm($emailAddress);

        // Follow the link and reset the password...
        $this->client->request('GET', $this->getPasswordResetUrlFromMessage());
        $newPassword = 'Banana1234';
        $this->client->submitForm('Reset password', [
            'change_password[password1]' => $newPassword,
            'change_password[password2]' => $newPassword,
        ]);

        $this->assertEquals('/login', $this->client->getRequest()->getRequestUri());

        // Check that the old password no longer works...
        $this->submitLoginForm($emailAddress, 'password');
        $this->assertEquals('/login', $this->client->getRequest()->getRequestUri());

        // Check that the new password does...
        $this->submitLoginForm($emailAddress, $newPassword);
        $this->assertEquals('/travel-diary', $this->client->getRequest()->getRequestUri());
    }

    public function dataPasswordResetFormValidation(): array {
        return [
            'All empty' => ['', '', false],
            'One empty' => ['Banana1234', '', false],
            'Other empty' => ['', 'Banana1234', false],
            'Non-matching' => ['Banana1234', 'Panana1234', false],
            'Matching: Too short' => ['Banana1', 'Banana1', false],
            'Matching: No upper' => ['banana1234', 'banana1234', false],
            'Matching: No lower' => ['BANANA1234', 'BANANA1234', false],
            'Matching: No number' => ['BananaSplit', 'BananaSplit', false],
            'Matching: Meets requirements' => ['Banana12', 'Banana12', true],
        ];
    }

    /**
     * @dataProvider dataPasswordResetFormValidation
     */
    public function testPasswordResetFormValidation(string $password1, string $password2, bool $expectedToSucceed): void
    {
        // Submit the password reset form...
        $this->submitPasswordResetForm('diary-keeper-adult@example.com');

        // Follow the link and reset the password...
        $this->client->request('GET', $this->getPasswordResetUrlFromMessage());
        $this->client->submitForm('Reset password', [
            'change_password[password1]' => $password1,
            'change_password[password2]' => $password2,
        ]);

        $this->assertEquals($expectedToSucceed, $this->client->getRequest()->getRequestUri() === '/login');
    }
}
