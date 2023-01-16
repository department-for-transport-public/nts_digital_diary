<?php

namespace App\Utility\Screenshots;

class DiaryKeeperScreenshotter extends AbstractScreenshotter
{
    /**
     * @throws ScreenshotsException
     */
    public function diaryFlow(string $userIdentifier, string $password): void
    {
        // View landing page
        $this->goto("/");
        $this->screenshot('1-landing.png');

        // View login page
        $this->goto("/login");
        $this->screenshot('2-login.png');

        // Login and view dashboard
        $this->submit([
            'Email' => $userIdentifier,
            'Password' => $password,
            'Remember me' => true,
        ], 'Sign in');
        $this->screenshot('3a-dashboard-empty.png');

        // View "practice diary" page
        $this->clickLinkWithText('View practice diary');
        $this->screenshot('4-practice-diary.png');
        $this->page->goBack([]);

        // View "Day 2"
        $this->clickLinkWithText('View', 1);
        $this->screenshot('view-day/1a-dashboard-empty.png');
        $this->page->goBack([]);

        // Add journey
        $this->addJourneyFlow();

        // Various permutations of method-of-transport form
        $this->fillForm(['Bus/Coach' => true]);
        $this->screenshot('add-stage/1a-method-of-transport.png');
        $this->fillForm(['Other private transport' => true]);
        $this->screenshot('add-stage/1b-method-of-transport.png');
        $this->fillForm(['Other public transport' => true]);
        $this->screenshot('add-stage/1c-method-of-transport.png');

        // Various different flows for adding stages
        $this->addCarStageFlow();
        $this->clickLinkWithText('Add a stage');
        $this->addWalkingStageFlow();
        $this->clickLinkWithText('Add a stage');
        $this->addTrainStageFlow();

        // Re-order stages
        $this->clickLinkWithText('Re-order stages');
        $this->screenshot('add-stage/2-reorder-stages.png');
        $this->page->goBack([]);

        // Journey dashboard, delete journey, delete stage
        $this->clickLinkWithText('Stage 1', 0, false);
        $this->page->click('body', []); // Remove focus from link
        $this->screenshot('view-journey/1-dashboard.png');

        $this->clickLinkWithText('Delete this stage');
        $this->screenshot('view-journey/2-delete-stage.png');
        $this->page->goBack([]);

        $this->clickLinkWithText('Delete this journey');
        $this->screenshot('view-journey/3-delete-journey.png');
        $this->page->goBack([]);

        // Share journey
        $this->shareJourneyFlow();

        // Repeat journey
        $this->repeatJourneyFlow();

        // Return journey
        $this->returnJourneyFlow();

        // Day dashboard, notes
        $this->clickLinkWithTextThatStartsWith('Back to day');
        $this->clickLinkWithTextThatStartsWith('Add notes');
        $this->screenshot('view-day/2-notes.png');

        $this->submit([
            'Notes for day' => 'I was not quite sure about the time for the return journey.',
        ], 'Save notes');
        $this->screenshot('view-day/1b-dashboard-filled.png');

        // Diary dashboard
        $this->clickLinkWithTextThatStartsWith('Back to diary');
        $this->screenshot('3b-dashboard-in-progress.png');

        // Mark diary as complete
        $this->clickLinkWithText('Mark travel diary as complete');
        $this->screenshot('5-mark-diary-as-complete.png');

        $this->submit([], 'Mark travel diary as complete');
        $this->screenshot('3c-dashboard-complete.png');

        $this->clickLinkWithText('Logout');
    }

    /**
     * @throws ScreenshotsException
     */
    public function addJourneyFlow(): void
    {
        $this->clickLinkWithText('Add journey');
        $this->screenshot('add-journey/1-intro.png');

        $this->submit([
            'Where did the journey start?' => [
                'Home' => true,
            ],
            'Where did you go?' => [
                'Somewhere else' => true,
                'Specify where you went' => 'Portsmouth',
            ],
        ], 'Continue');
        $this->screenshot('add-journey/2-travel-times.png');

        $this->submit([
            'What time did you start the journey?' => [
                'Hour' => '11',
                'Minute' => '20',
                'am' => true,
            ],
            'What time did you finish the journey?' => [
                'Hour' => '12',
                'Minute' => '30',
                'pm' => true,
            ],
        ], 'Continue');
        $this->screenshot('add-journey/3-journey-purpose.png');

        $this->submit([
            'What was the main purpose of this journey?' => 'To go shopping for groceries',
        ], 'Save and continue');

        $this->screenshot('add-journey/4-intermediary-screen.png');
        $this->submit([], 'Continue');
    }

    /**
     * @throws ScreenshotsException
     */
    public function addCarStageFlow(): void
    {
        $this->submit([
            'Car' => true,
        ], 'Continue');
        $this->screenshot('add-stage/private/2-more-details.png');

        $this->submit([
            'Distance' => '5',
            'Miles' => true,
            'How long did you spend travelling, in minutes' => '10',
            'Adults' => '1',
            'Children' => '1',
        ], 'Continue');
        $this->fillForm(['Other' => true]);
        $this->screenshot('add-stage/private/3-which-vehicle.png');

        $this->submit([
            'Other' => true,
            'Describe the vehicle you used' => "Friend's car 🚗",
        ], 'Continue');
        $this->screenshot('add-stage/private/4-parking-costs-and-driver.png');

        $this->submit([
            'Driver' => true,
            'How much did you pay for parking' => '3.50',
        ], 'Save and continue');
    }

    /**
     * @throws ScreenshotsException
     */
    public function addWalkingStageFlow(): void
    {
        $this->submit([
            'Walk or run' => true,
        ], 'Continue');
        $this->screenshot('add-stage/non-motorised/2-more-details.png');

        $this->submit([
            'Distance' => '1',
            'Miles' => true,
            'How long did you spend travelling, in minutes' => '5',
            'Adults' => '1',
            'Children' => '1',
        ], 'Save and continue');
    }

    /**
     * @throws ScreenshotsException
     */
    public function addTrainStageFlow(): void
    {
        $this->submit([
            'Train' => true,
        ], 'Continue');
        $this->screenshot('add-stage/public/2-more-details.png');

        $this->submit([
            'Distance' => '18',
            'Miles' => true,
            'How long did you spend travelling, in minutes' => '45',
            'Adults' => '1',
            'Children' => '1',
        ], 'Continue');
        $this->screenshot('add-stage/public/3-ticket-type.png');

        $this->submit([
            'Tell us about the ticket' => 'Anytime return',
        ], 'Continue');
        $this->screenshot('add-stage/public/4-ticket-cost-and-boardings.png');

        $this->submit([
            'How much did you pay' => '8.50',
            'How many times did you board' => '1',
        ], 'Save and continue');
    }

    /**
     * @throws ScreenshotsException
     */
    public function shareJourneyFlow(): void
    {
        $this->clickLinkWithText('Share this journey');
        $this->screenshot('share-journey/1-intro.png');

        $this->submit([], 'Continue');
        $this->screenshot('share-journey/2-who-to-share-with.png');

        $this->submit([
            'Linda' => true,
        ], 'Continue');
        $this->screenshot('share-journey/3-journey-purposes.png');

        $this->submit([
            'Purpose of journey for Linda' => 'To go shopping for groceries',
        ], 'Continue');
        $this->screenshot('share-journey/4-details-for-stage-1.png');

        $this->submit([
            'Parking costs paid by Linda' => '',
        ], 'Continue');
        $this->screenshot('share-journey/5-details-for-stage-3.png');

        $this->submit([], 'Save and continue'); // N.B. Already pre-filled
    }

    /**
     * @return void
     * @throws ScreenshotsException
     */
    public function returnJourneyFlow(): void
    {
        $this->clickLinkWithText('Make a return of this journey');
        $this->screenshot('return-journey/1-intro.png');

        $this->submit([], 'Continue');
        $this->screenshot('return-journey/2-which-day.png');

        $this->submit([
            'Day 2' => true,
        ], 'Continue');
        $this->screenshot('return-journey/3-times.png');

        $this->submit([
            'What time did you start' => [
                'Hour' => '2',
                'Minute' => '30',
                'pm' => true,
            ],
            'What time did you finish' => [
                'Hour' => '3',
                'Minute' => '40',
                'pm' => true,
            ],
        ], 'Continue');
        $this->screenshot('return-journey/4-details-for-stage-1.png');

        $this->submit([], 'Continue'); // N.B. Pre-filled
        $this->screenshot('return-journey/5-details-for-stage-2.png');

        $this->submit([], 'Continue'); // N.B. Pre-filled
        $this->screenshot('return-journey/6-details-for-stage-3.png');

        $this->submit([], 'Save and continue'); // N.B. Pre-filled
    }

    /**
     * @return void
     * @throws ScreenshotsException
     */
    public function repeatJourneyFlow(): void
    {
        $this->clickLinkWithText('Make this journey again');
        $this->screenshot('repeat-journey/1-intro.png');

        $this->submit([], 'Continue');
        $this->screenshot('repeat-journey/2-which-day.png');

        $this->submit([
            'Day 2' => true,
        ], 'Continue');
        $this->screenshot('repeat-journey/3-journey-purpose.png');

        $this->submit([], 'Continue'); // N.B. Pre-filled
        $this->screenshot('repeat-journey/4-what-time.png');

        $this->submit([
            'What time did you start the journey?' => [
                'Hour' => '11',
                'Minute' => '20',
                'am' => true,
            ],
            'What time did you finish the journey?' => [
                'Hour' => '12',
                'Minute' => '30',
                'pm' => true,
            ],
        ], 'Continue');
        $this->screenshot('repeat-journey/5-details-for-stage-1.png');

        $this->submit([], 'Continue'); // N.B. Pre-filled
        $this->screenshot('repeat-journey/5-details-for-stage-2.png');

        $this->submit([], 'Continue'); // N.B. Pre-filled
        $this->screenshot('repeat-journey/5-details-for-stage-3.png');

        $this->submit([], 'Save and continue'); // N.B. Pre-filled
    }
}