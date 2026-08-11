<?php

namespace App\Tests\Functional\Interviewer;

use App\Tests\Functional\Auth\AbstractAuthTestCase;
use Symfony\Component\HttpFoundation\Request;

class ChangePasswordTest extends AbstractAuthTestCase
{
    public function testChangePassword(): void
    {
        // Log in as interviewer
        $this->submitLoginForm('interviewer@example.com', 'password');
        $this->assertEquals('/interviewer', $this->client->getRequest()->getRequestUri());

        // Navigate to change-password page and submit with valid data
        $this->client->request(Request::METHOD_GET, '/interviewer/change-password');
        $this->client->submitForm('Change password', [
            'change_password[currentPassword]' => 'password',
            'change_password[password1]' => 'Banana1234',
            'change_password[password2]' => 'Banana1234',
        ]);

        // Should redirect to dashboard with success banner
        $this->assertEquals('/interviewer', $this->client->getRequest()->getRequestUri());
        $this->assertStringContainsString('Password changed', $this->client->getCrawler()->text());

        // Log out
        $this->client->clickLink('Logout');

        // Verify old password no longer works
        $this->submitLoginForm('interviewer@example.com', 'password');
        $this->assertEquals('/login', $this->client->getRequest()->getRequestUri());

        // Verify new password works
        $this->submitLoginForm('interviewer@example.com', 'Banana1234');
        $this->assertEquals('/interviewer', $this->client->getRequest()->getRequestUri());
    }

    public function dataChangePasswordFormValidation(): array
    {
        return [
            'All empty' => ['', '', '', false],
            'Wrong current password' => ['wrong', 'Banana1234', 'Banana1234', false],
            'New passwords do not match' => ['password', 'Banana1234', 'Panana1234', false],
            'Too short' => ['password', 'Banana1', 'Banana1', false],
            'No uppercase' => ['password', 'banana1234', 'banana1234', false],
            'No lowercase' => ['password', 'BANANA1234', 'BANANA1234', false],
            'No number' => ['password', 'BananaSplit', 'BananaSplit', false],
            'Valid' => ['password', 'Banana1234', 'Banana1234', true],
        ];
    }

    /**
     * @dataProvider dataChangePasswordFormValidation
     */
    public function testChangePasswordFormValidation(string $currentPassword, string $password1, string $password2, bool $expectedToSucceed): void
    {
        // Log in as interviewer
        $this->submitLoginForm('interviewer@example.com', 'password');

        // Navigate to change-password page and submit form
        $this->client->request(Request::METHOD_GET, '/interviewer/change-password');
        $this->client->submitForm('Change password', [
            'change_password[currentPassword]' => $currentPassword,
            'change_password[password1]' => $password1,
            'change_password[password2]' => $password2,
        ]);

        $this->assertEquals(
            $expectedToSucceed,
            $this->client->getRequest()->getRequestUri() === '/interviewer',
        );
    }
}
