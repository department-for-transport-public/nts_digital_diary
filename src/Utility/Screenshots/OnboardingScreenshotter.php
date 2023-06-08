<?php

namespace App\Utility\Screenshots;

class OnboardingScreenshotter extends AbstractScreenshotter
{
    /**
     * @throws ScreenshotsException
     */
    public function onboardingFlow(string $passcode1, string $passcode2): string
    {
        // ----- Initial details -----
        // View login page
        $this->goto("/onboarding");
        $this->screenshot('1-login.png');

        // Log in and view intro
        $this->submit([
            'First passcode' => $passcode1,
            'Second passcode' => $passcode2,
        ], 'Sign in');
        $this->screenshot('2-intro.png');

        // Click "continue" and view household details
        $this->clickButtonWithText('Continue');
        $this->screenshot('3-household-details.png');

        // Fill out and view dashboard
        $now = new \DateTime();
        $this->submit([
            'Address number' => '1',
            'Household number' => '2',
            'Day' => '2',
            'Month' => $now->format('m'),
            'Year' => $now->format('Y'),
        ], 'Save and continue');
        $this->screenshot('4a-dashboard-empty.png');

        // ----- First diary keeper -----
        // Click "Add a diary keeper"
        $this->clickLinkWithText('Add a diary keeper');
        $this->screenshot('add-first-diary-keeper/1-add-diary-keeper.png');

        // Fill out details
        $this->submit([
            'Name' => 'Mark',
            'Number' => '1',
            'Yes' => true,
        ], 'Continue');
        $this->screenshot('add-first-diary-keeper/2a-diary-media-type.png');

        $this->fillForm([
            'Digital diary' => true,
        ]);
        $this->screenshot('add-first-diary-keeper/2b-sharing-options.png');

        // Fill out identity form
        $userIdentifier = 'mark@example.com';
        $this->submit([
            'Enter their email address' => $userIdentifier,
        ], 'Save and continue');

        // ----- Second diary keeper -----
        // Choose to add another diary keeper
        $this->submit([
            'Yes' => true,
        ], 'Continue');

        $this->screenshot('add-second-diary-keeper/1-add-diary-keeper.png');

        // Fill out details
        $this->submit([
            'Name' => 'Linda',
            'Number' => '2',
            'No' => true,
        ], 'Continue');
        $this->screenshot('add-second-diary-keeper/2a-diary-media-type.png');

        $this->fillForm([
            'Digital diary' => true,
        ]);
        $this->screenshot('add-second-diary-keeper/2b-sharing-options.png');

        // Fill out identity form
        $this->submit([
            'Mark (mark@example.com)' => true,
        ], 'Save and continue');

        // We don't want to add another
        $this->submit([
            'No' => true,
        ], 'Continue');

        // ----- Vehicle -----
        // Click "Add a vehicle"
        $this->clickLinkWithText('Add a vehicle');
        $this->screenshot('add-vehicle/1-add-vehicle.png');

        // Fill out and view dashboard
        $this->submit([
            'Name' => "Blue golf",
            'Car' => true,
            'CAPI number' => "1",
            "Mark" => true,
        ], 'Save vehicle');

        // Populated dashboard screenshot
        $this->screenshot('4b-dashboard-populated.png');

        // Submission check screen
        $this->clickLinkWithText('Submit household');
        $this->screenshot('5-check-submission.png');

        // Submission confirmation screen
        $this->clickButtonWithText('Confirm details and submit');
        $this->screenshot('6-submit-confirmation.png');

        return $userIdentifier;
    }
}