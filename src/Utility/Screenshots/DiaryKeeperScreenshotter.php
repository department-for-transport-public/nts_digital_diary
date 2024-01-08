<?php

namespace App\Utility\Screenshots;

use App\Features;
use Nesk\Puphpeteer\Resources\Browser;

class DiaryKeeperScreenshotter extends AbstractScreenshotter
{
    public function __construct(
        Features $features,
        Browser $browser,
        string $screenshotsBaseDir,
        string $hostname,
        protected bool $addExtraStages=false
    ) {
        parent::__construct($features, $browser, $screenshotsBaseDir, $hostname);
    }

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

        if ($this->addExtraStages) {
            $this->clickLinkWithText('Add a stage');
            $this->addPublicOtherStageFlow();
            $this->clickLinkWithText('Add a stage');
            $this->addCoachStageFlow();
            $this->clickLinkWithText('Add a stage');
            $this->addPrivateOtherStageFlow();
        }

        // Re-order stages
        $this->clickLinkWithText('Re-order stages');
        $this->screenshot('add-stage/2-reorder-stages.png');
        $this->page->goBack([]);

        // Journey dashboard, delete journey, delete stage
        $this->clickLinkWithText('Stage 1', 0, false);
        $this->page->click('.govuk-heading-xl', []); // Remove focus from link
        $this->screenshot('view-journey/1-dashboard.png');
        $this->clickLinkWithText('Delete this stage');
        $this->screenshot('view-journey/2-delete-stage.png');
        $this->page->goBack([]);

        $this->clickLinkWithText('Delete this journey');
        $this->screenshot('view-journey/3-delete-journey.png');
        $this->page->goBack([]);

        // Share journey
        $this->shareJourneyFlow();

        $this->clickLinkWithText('My travel diary');
        $this->clickLinkWithText('View', 1); // We want to repeat onto day 2

        // Repeat journey
        $this->repeatJourneyFlow();

        $this->clickLinkWithText('My travel diary');
        $this->clickLinkWithText('View', 0); // Back to day 1
        $this->clickLinkWithText('View', 0); // View the first (and only) journey

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

        // Milometer readings
        $this->milometerReadingsFlow();

        $this->addJourneyForSplitterDemonstration(5, 'Home', 'Walk the dog');
        $this->addJourneyForSplitterDemonstration(6, 'Portsmouth', 'Collect a package');

        // Mark diary as complete
        $this->clickLinkWithText('Mark travel diary as complete');
        $this->screenshot('5-mark-diary-as-complete.png');

        $this->submit([], 'Mark travel diary as complete');
        $this->screenshot('3c-dashboard-complete.png');

        $this->satisfactionSurveyFlow();

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
                'Hour' => '1',
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

        $this->fillForm([
            '#driver_and_parking_parkingCost' => [
                'Yes' => true,
            ],
        ]);
        $this->screenshot('add-stage/private/4-parking-costs-and-driver.png');

        $this->submit([
            'Driver' => true,
            '#driver_and_parking_parkingCost' => [
                'Yes' => true,
                'How much did you pay for parking' => '8.50',
            ],
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


        $this->fillForm([
            '#ticket_cost_and_boardings_ticketCost' => [
                'Yes' => true,
            ],
        ]);
        $this->screenshot('add-stage/public/4-ticket-cost-and-boardings.png');

        $this->submit([
            '#ticket_cost_and_boardings_ticketCost' => [
                'Yes' => true,
                'How much did your ticket cost' => '8.50',
            ],
            'How many times did you board' => '1',
        ], 'Save and continue');
    }

    /**
     * @throws ScreenshotsException
     */
    public function addCoachStageFlow(): void
    {
        $this->submit([
            'Bus/Coach' => true,
            '#method_other-bus-or-coach' => 'Local red bus',
        ], 'Continue');

        $this->submit([
            'Distance' => '10',
            'Miles' => true,
            'How long did you spend travelling, in minutes' => '15',
            'Adults' => '1',
            'Children' => '1',
        ], 'Continue');

        $this->submit([
            'Tell us about the ticket' => 'Single',
        ], 'Continue');

        $this->submit([
            '#ticket_cost_and_boardings_ticketCost' => [
                'Yes' => true,
                'How much did your ticket cost' => '4.00',
            ],
            'How many times did you board' => '1',
        ], 'Save and continue');
    }

    /**
     * @throws ScreenshotsException
     */
    public function addPublicOtherStageFlow(): void
    {
        $this->submit([
            'Other public transport' => true,
            '#method_other-other-public' => 'Hovercraft'
        ], 'Continue');

        $this->submit([
            'Distance' => '30',
            'Miles' => true,
            'How long did you spend travelling, in minutes' => '25',
            'Adults' => '1',
            'Children' => '1',
        ], 'Continue');

        $this->submit([
            'Tell us about the ticket' => 'Single hover-pass',
        ], 'Continue');

        $this->submit([
            '#ticket_cost_and_boardings_ticketCost' => [
                'Yes' => true,
                'How much did your ticket cost' => '3.75',
            ],
            'How many times did you board' => '1',
        ], 'Save and continue');
    }

    /**
     * @throws ScreenshotsException
     */
    public function addPrivateOtherStageFlow(): void
    {
        $this->submit([
            'Other private transport' => true,
            '#method_other-other-private' => 'Golf cart'
        ], 'Continue');

        $this->submit([
            'Distance' => '2',
            'Miles' => true,
            'How long did you spend travelling, in minutes' => '5',
            'Adults' => '1',
            'Children' => '1',
        ], 'Continue');

        $this->submit([
            'Describe the vehicle you used' => "Borrowed golf cart",
        ], 'Continue');

        $this->submit([
            'Driver' => true,
            '#driver_and_parking_parkingCost' => [
                'No' => true,
            ],
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

        $stageTypes = $this->addExtraStages ?
            [1 => 'parking', 3 => 'ticket', 4 => 'ticket', 5 => 'ticket', 6 => 'parking'] :
            [1 => 'parking', 3 => 'ticket'];

        $lastStageKey = array_key_last($stageTypes);

        $getData = function(string $stageType): array {
            return $stageType === 'walk' ?
                [] :
                [
                    "#stage_details_collection_0_{$stageType}Cost" => [
                        'Yes' => true
                    ],
                ];
        };

        $wizardStepNum = 3;
        foreach($stageTypes as $stageNum => $stageType) {
            $wizardStepNum += 1;

            $this->fillForm($getData($stageType));
            $this->screenshot("share-journey/{$wizardStepNum}-details-for-stage-{$stageNum}.png");

            $buttonText = ($stageNum === $lastStageKey) ?
                'Save and continue' :
                'Continue';

            $this->submit($getData($stageType), $buttonText); // N.B. Pre-filled
        }
    }

    /**
     * @throws ScreenshotsException
     */
    public function returnJourneyFlow(): void
    {
        $this->clickLinkWithTextThatStartsWith('Make a return of this journey');
        $this->screenshot('return-journey/1-intro.png');

        $this->submit([], 'Continue');
        $this->screenshot('return-journey/2-which-day.png');

        $this->submit([
            'Day 1' => true,
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

        $stageTypes = $this->addExtraStages ?
            ['parking', 'ticket', 'ticket', 'ticket', 'walk', 'parking'] :
            ['ticket', 'walk', 'parking'];

        $lastStageKey = array_key_last($stageTypes);

        $getFormData = function(string $stageType): array {
            return $stageType === 'walk' ?
                [] :
                [
                    "#stage_details_{$stageType}Cost" => [
                        'Yes' => true
                    ],
                ];
        };

        foreach($stageTypes as $i => $stageType) {
            $stageNum = $i + 1;
            $wizardStepNum = $i + 4;

            $this->fillForm($getFormData($stageType));
            $this->screenshot("return-journey/{$wizardStepNum}-details-for-stage-{$stageNum}.png");

            $buttonText = ($i === $lastStageKey) ?
                'Save and continue' :
                'Continue';

            $this->submit($getFormData($stageType), $buttonText); // N.B. Pre-filled
        }
    }

    /**
     * @throws ScreenshotsException
     */
    public function repeatJourneyFlow(): void
    {
        $this->clickLinkWithText('Repeat a journey');
        $this->screenshot('repeat-journey/1-intro.png');

        $this->submit([], 'Continue');
        $this->screenshot('repeat-journey/2-which-day.png');

        $this->submit([
            'Day 1' => true,
        ], 'Continue');
        $this->screenshot('repeat-journey/3-which-journey.png');

        $this->submit([
            'From Home to Portsmouth' => true,
        ], 'Continue');
        $this->screenshot('repeat-journey/4-purpose.png');

        $this->submit([], 'Continue'); // N.B. Pre-filled
        $this->screenshot('repeat-journey/5-what-time.png');

        $this->submit([], 'Continue');
        $this->screenshot('repeat-journey/6-details-for-stage-1.png');

        $this->submit([], 'Continue'); // N.B. Pre-filled
        $this->screenshot('repeat-journey/7-details-for-stage-2.png');

        $this->submit([], 'Continue');
        $this->screenshot('repeat-journey/8-details-for-stage-3.png');

        if ($this->addExtraStages) {
            $this->submit([], 'Continue');
            $this->screenshot('repeat-journey/9-details-for-stage-4.png');

            $this->submit([], 'Continue');
            $this->screenshot('repeat-journey/10-details-for-stage-5.png');

            $this->submit([], 'Continue');
            $this->screenshot('repeat-journey/11-details-for-stage-6.png');
        }

        $this->submit([], 'Save and continue');
    }

    /**
     * @throws ScreenshotsException
     */
    protected function milometerReadingsFlow(): void
    {
        if (!$this->features->isEnabled(Features::MILOMETER)) {
            return;
        }

        $this->clickLinkWithText('Enter/edit milometer readings for "Blue golf"');
        $this->screenshot('milometer/1-enter-readings.png');

        $this->submit([
            'Miles' => true,
            'Milometer reading at start of diary week' => '17595',
            'Milometer reading at end of diary week' => '17843',
        ], 'Save');
    }

    /**
     * @throws ScreenshotsException
     */
    protected function addJourneyForSplitterDemonstration(int $day, string $sourceAndDestination, string $purpose): void
    {
        $this->clickLinkWithText('View', $day - 1);
        $this->clickLinkWithText('Add journey');

        $this->submit([
            'Where did the journey start?' => [
                $sourceAndDestination => true,
            ],
            'Where did you go?' => [
                $sourceAndDestination=> true,
            ],
        ], 'Continue');

        $this->submit([
            'What time did you start the journey?' => [
                'Hour' => '8',
                'Minute' => '00',
                'am' => true,
            ],
            'What time did you finish the journey?' => [
                'Hour' => '8',
                'Minute' => '30',
                'am' => true,
            ],
        ], 'Continue');

        $this->submit([
            'What was the main purpose of this journey?' => $purpose,
        ], 'Save and continue');

        $this->submit([], 'Continue');

        $this->submit([
            'Walk or run' => true,
        ], 'Continue');

        $this->submit([
            'Distance' => '1',
            'Miles' => true,
            'How long did you spend travelling, in minutes' => '20',
            'Adults' => '1',
            'Children' => '0',
        ], 'Save and continue');

        $this->clickLinkWithText('My travel diary');
    }

    protected function satisfactionSurveyFlow(): void
    {
        $this->clickLinkWithText('this short survey');
        $this->screenshot('satisfaction-survey/1-ease-of-use.png');

        $this->submit([
            'Very easy' => true,
        ], 'Continue');

        $this->screenshot('satisfaction-survey/2a-diary-burden.png');
        $this->fillForm(['Not at all burdensome' => true]);

        $this->screenshot('satisfaction-survey/2b-diary-burden_not-at-all.png');
        $this->fillForm([
            'A little burdensome' => true,
            'Other' => true,
        ]);

        $this->screenshot('satisfaction-survey/2c-diary-burden_a-little-or-more.png');

        $this->submit([
            'Not at all burdensome' => true,
        ], 'Continue');

        $this->screenshot('satisfaction-survey/3a-devices-used.png');

        $this->fillForm([
            'Other' => true,
        ]);
        $this->screenshot('satisfaction-survey/3b-devices-used_other.png');

        $this->submit([
            'Desktop or laptop' => true,
            'Other' => true,
            'What device did you use?' => 'Tomy toy computer',
        ], 'Continue');

        $this->screenshot('satisfaction-survey/4-diary-completion-patterns.png');

        $this->submit([
            'Multiple times a day' => true,
            'Yes' => true,
        ], 'Continue');

        $this->screenshot('satisfaction-survey/5a-diary-methodologies.png');
        $this->fillForm([
            'Other approach' => true,
        ]);
        $this->screenshot('satisfaction-survey/5b-diary-methodologies_other.png');

        $this->submit([
            'Online questionnaire' => true,
        ], 'Save and continue');
        $this->screenshot('satisfaction-survey/6-questionnaire-complete.png');
    }
}