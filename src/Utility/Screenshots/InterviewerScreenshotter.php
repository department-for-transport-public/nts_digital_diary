<?php

namespace App\Utility\Screenshots;

use App\Utility\XPath;

class InterviewerScreenshotter extends AbstractScreenshotter
{
    /**
     * @throws ScreenshotsException
     */
    public function loginAndOnboardingCodesFlow(): void
    {
        // 1. View login page
        $this->goto("/login");
        $this->screenshot('1-login.png');

        // 2. Log in and view Dashboard
        $this->submit([
            'Email' => 'screenshots@example.com',
            'Password' => 'password',
            'Remember me' => true,
        ], 'Sign in');
        $this->screenshot('2-dashboard.png');

        $this->clickLinkWithText('View');
        $this->screenshot('3-area.png');

        $this->clickLinkWithText('View Onboarding Codes');
        $this->screenshot('4-onboarding-codes.png');
        $this->page->goBack([]);

        $this->clickLinkWithText('View');
        $this->screenshot('5a-household.png');

        $this->clickLinkWithTextThatStartsWith('View');
        $this->screenshot('6a-diary-keeper.png');

        $this->clickLinkWithTextThatStartsWith('Mark diary as approved');
        $this->screenshot('7a-mark-diary-approved.png');

        $this->submit(['#double_confirm_action_confirmation' => true], 'Mark diary as approved');

        // Approve second diary keeper
        $this->clickLinkWithTextThatStartsWith('View', 1);
        $this->clickLinkWithTextThatStartsWith('Mark diary as approved');
        $this->submit(['#double_confirm_action_confirmation' => true], 'Mark diary as approved');

        $this->screenshot('5b-household-with-approved-diary-keepers.png');

        $this->clickLinkWithTextThatStartsWith('View');
        $this->screenshot('6b-diary-keeper-approved.png');

        $this->clickLinkWithTextThatStartsWith('Un-approve diary');
        $this->screenshot('7b-unapprove-diary.png'); // N.B. We don't actually unapprove it

        $this->clickLinkWithTextThatStartsWith('Cancel');
        $this->clickLinkWithTextThatStartsWith('Submit for processing');
        $this->screenshot('8-confirm-household-submission.png');

        $this->submit([], 'Confirm submission');
        $this->screenshot('9-household-submitted.png');

        $this->clickLinkWithText('Logout');
    }

    /**
     * @throws ScreenshotsException
     */
    public function retrieveOnboardingCodes(): array
    {
        // Log in
        $this->goto("/login");
        $this->submit([
            'Email' => 'screenshots@example.com',
            'Password' => 'password',
            'Remember me' => true,
        ], 'Sign in');

        // Click "View" link
        $this->clickLinkWithText('View');

        // Click "View Onboarding Codes" button
        $this->clickLinkWithText('View Onboarding Codes');

        // Grab top pair of codes
        $xpath = XPath::create()->withTag('div')->withClass('onboarding-codes__code-pair');
        $element = $this->findElement($xpath);

        $text = $element->getProperty('innerText')->toString();

        if (!preg_match('/Passcode 1: (?P<passcode1>\d+)\nPasscode 2: (?P<passcode2>\d+)/i', $text, $matches)) {
            throw new ScreenshotsException('Failed to retrieve Onboarding Codes', $this->page);
        }

        // Log out
        $this->clickLinkWithText('Logout');

        return [
            $matches['passcode1'],
            $matches['passcode2'],
        ];
    }
}