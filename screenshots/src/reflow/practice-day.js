const {submit, clickLinkWithText} = require("../common")

module.exports.practiceDayFlow = async function(page, screenshot) {
    await clickLinkWithText(page, 'Use the practice area')
    await screenshot('practice-day/1-dashboard-empty.png')

    // Add a simple journey with one walking stage (no screenshots)
    await clickLinkWithText(page, 'Add journey')

    await submit(page, {
        'A new journey': true,
    }, 'Continue')

    await submit(page, {
        'Where did the journey start?': {'Home': true},
        'Where did you go?': {
            'Somewhere else': true,
            'Specify where you went': 'Wibblestone park',
        },
    }, 'Continue')

    await submit(page, {
        'What time did you start the journey?': {
            'Hour': '10',
            'Minute': '00',
            'am': true,
        },
        'What time did you finish the journey?': {
            'Hour': '10',
            'Minute': '30',
            'am': true,
        },
        'What was the main purpose of this journey?': 'To go for a walk in the park',
    }, 'Save and continue')

    await submit(page, {'Walk or run': true}, 'Continue')

    await submit(page, {
        'Distance': '1',
        'Miles': true,
        'How long did you spend travelling': '20',
        'Adults': '1',
        'Children': '0',
    }, 'Save and continue')

    await finishJourney(page)

    // After saving the stage, we're on the journey view page
    // Navigate back to practice day dashboard for the populated screenshot
    await clickLinkWithText(page, 'back to the practice day')
    await screenshot('practice-day/2-dashboard.png')

    // View the journey to show off the "Practice day" heading
    await clickLinkWithText(page, 'View')
    await screenshot('practice-day/3-view-journey.png')

    // Navigate back to the main dashboard
    await clickLinkWithText(page, 'back to the practice day')
    await clickLinkWithText(page, 'Back to diary overview')
}

async function finishJourney(page) {
    await submit(page, {
        'Yes, I had reached Wibblestone park': true,
    }, 'Continue')
}
