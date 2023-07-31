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

        $this->clickLinkWithText('Impersonate');
        $this->journeySplitterHomeToHomeFlow();
        $this->journeySplitterNonHomeToNonHomeFlow();
        $this->clickLinkWithText('Exit');

        $this->clickLinkWithText('View / amend diaries');
        $this->setViewportSize($this->page, 1920);
        $this->screenshot('6a-compare-household-one.png');

        $this->clickLinkWithText('Linda', 0, false);
        $this->screenshot('6b-compare-household-two.png');
        $this->setViewportSize($this->page);

        $this->page->goBack([]);
        $this->page->goBack([]);

        $this->clickLinkWithTextThatStartsWith('Summary');
        $this->screenshot('7a-diary-summary.png');

        $this->clickLinkWithTextThatStartsWith('Mark diary as approved');
        $this->screenshot('8a-mark-diary-approved.png');

        $this->submit([
            '#approve_diary_confirm_action_verifyEmptyDays' => true,
            "#approve_diary_confirm_action_alsoVerified_0" => true,
            "#approve_diary_confirm_action_alsoVerified_1" => true,
            "#approve_diary_confirm_action_alsoVerified_2" => true,
            "#approve_diary_confirm_action_alsoVerified_3" => true,
        ], 'Mark diary as approved');

        // Approve second diary keeper
        $this->clickLinkWithTextThatStartsWith('Summary', 1);

        // Can only approve when diary is complete
        $this->clickLinkWithText('Impersonate');
        $this->clickLinkWithText('Mark travel diary as complete');
        $this->submit([], 'Mark travel diary as complete');
        $this->clickLinkWithText('Exit');

        $this->clickLinkWithTextThatStartsWith('Mark diary as approved');
        $this->submit([
            '#approve_diary_confirm_action_verifyEmptyDays' => true,
            "#approve_diary_confirm_action_alsoVerified_0" => true,
            "#approve_diary_confirm_action_alsoVerified_1" => true,
            "#approve_diary_confirm_action_alsoVerified_2" => true,
            "#approve_diary_confirm_action_alsoVerified_3" => true,
        ], 'Mark diary as approved');

        $this->screenshot('5b-household-with-approved-diary-keepers.png');

        $this->clickLinkWithTextThatStartsWith('Summary');
        $this->screenshot('7b-diary-keeper-approved.png');

        $this->clickLinkWithTextThatStartsWith('Un-approve diary');
        $this->screenshot('8b-unapprove-diary.png'); // N.B. We don't actually unapprove it

        $this->clickLinkWithTextThatStartsWith('Cancel');
        $this->clickLinkWithTextThatStartsWith('Submit for processing');
        $this->screenshot('9-confirm-household-submission.png');

        $this->submit([], 'Confirm submission');
        $this->screenshot('10-household-submitted.png');

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

    /**
     * @throws ScreenshotsException
     */
    protected function journeySplitterHomeToHomeFlow(): void
    {
        $this->clickLinkWithText('View', 4); // Day 5
        $this->clickLinkWithText('View');
        $this->clickLinkWithText('Split journey (Interviewer only)');

        $this->screenshot('journey-splitter/home-to-home/1-intro.png');

        $this->submit([], 'Continue');

        $this->screenshot('journey-splitter/home-to-home/2-midpoint.png');

        $this->submit([
            'Portsmouth' => true,
        ], 'Save and continue');

        $this->clickLinkWithText('Back to diary overview');
    }

    /**
     * @throws ScreenshotsException
     */
    protected function journeySplitterNonHomeToNonHomeFlow(): void
    {
        $this->clickLinkWithText('View', 5); // Day 6
        $this->clickLinkWithText('View');
        $this->clickLinkWithText('Split journey (Interviewer only)');

        $this->screenshot('journey-splitter/other-to-other/1-intro.png');

        $this->submit([], 'Continue');

        $this->screenshot('journey-splitter/other-to-other/2-midpoint.png');

        $this->submit([
            'Somewhere else' => true,
            'What was the location?' => 'Chichester'
        ], 'Continue');
        $this->screenshot('journey-splitter/other-to-other/2-purpose.png');

        $this->submit([
            'What was the purpose of the journey from Chichester to Portsmouth?' => "Return to friend's house",
        ], 'Save and continue');

        $this->clickLinkWithText('Back to diary overview');
    }
}