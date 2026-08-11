const {submit, clickLinkWithText, getScreenshotter, fillForm, clickLinkWithTextThatStartsWith, executeCommand,
    clickElementContainingText
} = require("./common")

const {fixtures} = require('./fixtures')
const {practiceDayFlow} = require('./practice-day')

module.exports.diaryFlow = async function(page, outputDir, userIdentifier, password, addExtraStages) {
    const screenshot = await getScreenshotter(page, outputDir  + 'diary-keeper/')

    // user starts by setting a password, then is automatically logged in
    await screenshot('0-user-preferences.png')

    await clickLinkWithText(page, 'Logout')
    await screenshot('1-landing.png')

    await clickLinkWithText(page, 'Sign in')
    await screenshot('2-login.png')
    await submit(page, {
        'Email': userIdentifier,
        'Password': password,
        'Remember me': true,
    }, 'Sign in')
    await screenshot('3a-dashboard-empty.png')

    await clickElementContainingText(page, 'span', 'Examples and practice', 0, false)
    await clickLinkWithText(page, 'View example day')
    await screenshot('4a-example-journeys.png')
    await page.goBack()

    await practiceDayFlow(page, screenshot)

    // View "Day 2"
    await clickLinkWithText(page, 'View', 1)
    await screenshot('view-day/1a-dashboard-empty.png')

    await page.goBack()

    await addJourneyFlow(page, screenshot)

    // Various permutations of method-of-transport form
    await fillForm(page, {'Bus/Coach': true})
    await screenshot('add-stage/1a-method-of-transport.png')
    await fillForm(page, {'Other private transport': true})
    await screenshot('add-stage/1b-method-of-transport.png')
    await fillForm(page, {'Other public transport': true})
    await screenshot('add-stage/1c-method-of-transport.png')

    // Various different flows for adding stages
    await addCarStageFlow(page, screenshot)
    await clickLinkWithText(page, 'Add a stage')
    await addWalkingStageFlow(page, screenshot)
    await clickLinkWithText(page, 'Add a stage')
    await addTrainStageFlow(page, screenshot)

    if (addExtraStages) {
        await clickLinkWithText(page, 'Add a stage')
        await addPublicOtherStageFlow(page)
        await clickLinkWithText(page, 'Add a stage')
        await addCoachStageFlow(page)
        await clickLinkWithText(page, 'Add a stage')
        await addPrivateOtherStageFlow(page)
    }

    // Re-order stages
    await clickLinkWithText(page,'Re-order stages')
    await screenshot('add-stage/2-reorder-stages.png')
    await page.goBack()

    // Journey dashboard, delete journey, delete stage
    await screenshot('view-journey/1-dashboard.png')
    await page.click('#step-nav-stage-1 button.app-step-nav__button--title', [])
    await page.click('.govuk-heading-xl', []) // Remove focus from link
    await screenshot('view-journey/1a-dashboard-open-stage.png')

    await clickLinkWithText(page,'Delete this stage')
    await screenshot('view-journey/2-delete-stage.png')
    await page.goBack()

    await clickLinkWithText(page,'Delete this journey')
    await screenshot('view-journey/3-delete-journey.png')
    await page.goBack()

    // Share journey
    await shareJourneyFlow(page, screenshot, addExtraStages)

    await clickLinkWithText(page, "Alice's travel diary")
    await clickLinkWithText(page, 'View', 1) // We want to repeat onto day 2

    // Repeat journey (we're on day 2's view here)
    await repeatJourneyFlow(page, screenshot, addExtraStages, 2)

    await clickLinkWithText(page, "Alice's travel diary")
    await clickLinkWithText(page, 'View', 0) // Back to day 1
    await clickLinkWithText(page, 'View', 0) // View the first (and only) journey

    // Return journey
    await returnJourneyFlow(page, screenshot, addExtraStages)

    // Day dashboard, notes
    await clickLinkWithTextThatStartsWith(page, 'back to day')
    await clickLinkWithTextThatStartsWith(page, 'Add notes')
    await screenshot('view-day/2-notes.png')

    await submit(page, {
        'Notes for day': 'I was not quite sure about the time for the return journey.',
    }, 'Save notes')
    await screenshot('view-day/1b-dashboard-filled.png')

    // Diary dashboard
    await clickLinkWithTextThatStartsWith(page, 'Back to diary')
    await screenshot('3b-dashboard-in-progress.png')

    // Milometer readings
    await milometerReadingsFlow(page, screenshot)

    await addJourneyForSplitterDemonstration(page, screenshot, 5, 'Home', 'Walk the dog')
    await addJourneyForSplitterDemonstration(page, screenshot, 6, 'Portsmouth', 'Collect a package')

    // Mark diary as complete
    await clickLinkWithText(page, 'Mark my travel diary as complete')
    await screenshot('5-mark-diary-as-complete.png')

    await submit(page, {}, 'Mark my travel diary as complete')
    await screenshot('3c-dashboard-complete.png')

    await satisfactionSurveyFlow(page, screenshot)

    await clickLinkWithText(page, 'Logout')
}

async function addJourneyFlow(page, screenshot) {
    await clickLinkWithText(page, 'Add journey')
    await screenshot('add-journey/1-intro.png')

    await submit(page, {
        'Where did the journey start?': {
            'Home': true,
        },
        'Where did you go?': {
            'Somewhere else': true,
            'Specify where you went': 'Portsmouth',
        },
    }, 'Continue')
    await screenshot('add-journey/2-travel-times.png')

    await submit(page, {
        'What time did you start the journey?': {
            'Hour': '11',
            'Minute': '20',
            'am': true,
        },
        'What time did you finish the journey?': {
            'Hour': '1',
            'Minute': '30',
            'pm': true,
        },
    }, 'Continue')
    await screenshot('add-journey/3-journey-purpose.png')

    await submit(page, {
        'What was the main purpose of this journey?': 'To go shopping for groceries',
    }, 'Save and continue')

    await screenshot('add-journey/4-intermediary-screen.png')
    await submit(page, {}, 'Continue')
}

async function addCarStageFlow(page, screenshot)
{
    await submit(page, {
        'Car': true,
    }, 'Continue')
    await screenshot('add-stage/private/2-more-details.png')

    await submit(page, {
        'Distance': '5',
        'Miles': true,
        'How long did you spend travelling': '10',
        'Adults': '1',
        'Children': '2',
    }, 'Continue')
    await fillForm(page, {'Other': true})
    await screenshot('add-stage/private/3-which-vehicle.png')

    await submit(page, {
        'Other': true,
        'Describe the vehicle you used': "Friend's car 🚗",
    }, 'Continue')

    await fillForm(page, {
        '#driver_and_parking_parkingCost': {'Yes': true}
    })
    await screenshot('add-stage/private/4-parking-costs-and-driver.png')

    await submit(page, {
        'Driver': true,
        '#driver_and_parking_parkingCost': {
            'Yes': true,
            'How much did you pay for parking': '8.50',
        },
    }, 'Save and continue')
}

async function addWalkingStageFlow(page, screenshot)
{
    await submit(page, {
        'Walk or run': true,
    }, 'Continue')
    await screenshot('add-stage/non-motorised/2-more-details.png')

    await submit(page, {
        'Distance': '1',
        'Miles': true,
        'How long did you spend travelling': '5',
        'Adults': '1',
        'Children': '2',
    }, 'Save and continue')
}

async function addTrainStageFlow(page, screenshot)
{
    await submit(page, {
        'Train': true,
    }, 'Continue')
    await screenshot('add-stage/public/2-more-details.png')

    await submit(page, {
        'Distance': '18',
        'Miles': true,
        'How long did you spend travelling': '45',
        'Adults': '1',
        'Children': '2',
    }, 'Continue')
    await screenshot('add-stage/public/3-ticket-type.png')

    await submit(page, {
        'Tell us about the ticket': 'Anytime return',
    }, 'Continue')


    await fillForm(page, {
        '#ticket_cost_and_boardings_ticketCost': {'Yes': true},
    })
    await screenshot('add-stage/public/4-ticket-cost-and-boardings.png')

    await submit(page, {
        '#ticket_cost_and_boardings_ticketCost': {
            'Yes': true,
            'How much did your ticket cost': '8.50',
        },
        'How many times did you board': '1',
    }, 'Save and continue')
}

async function addCoachStageFlow(page)
{
    await submit(page, {
        'Bus/Coach': true,
        '#method_other-bus-or-coach': 'Local red bus',
    }, 'Continue')

    await submit(page, {
        'Distance': '10',
        'Miles': true,
        'How long did you spend travelling': '15',
        'Adults': '1',
        'Children': '2',
    }, 'Continue')

    await submit(page, {
        'Tell us about the ticket': 'Single',
    }, 'Continue')

    await submit(page, {
        '#ticket_cost_and_boardings_ticketCost': {
            'Yes': true,
            'How much did your ticket cost': '4.00',
        },
        'How many times did you board': '1',
    }, 'Save and continue')
}

async function addPublicOtherStageFlow(page)
{
    await submit(page, {
        'Other public transport': true,
        '#method_other-other-public': 'Hovercraft',
    }, 'Continue')

    await submit(page, {
        'Distance': '30',
        'Miles': true,
        'How long did you spend travelling': '25',
        'Adults': '1',
        'Children': '2',
    }, 'Continue')

    await submit(page, {
        'Tell us about the ticket': 'Single hover-pass',
    }, 'Continue')

    await submit(page, {
        '#ticket_cost_and_boardings_ticketCost': {
            'Yes': true,
            'How much did your ticket cost': '3.75',
        },
        'How many times did you board': '1',
    }, 'Save and continue')
}

async function addPrivateOtherStageFlow(page)
{
    await submit(page, {
        'Other private transport': true,
        '#method_other-other-private': 'Golf cart',
    }, 'Continue')

    await submit(page, {
        'Distance': '2',
        'Miles': true,
        'How long did you spend travelling': '5',
        'Adults': '1',
        'Children': '2',
    }, 'Continue')

    await submit(page, {
        'Describe the vehicle you used': "Borrowed golf cart",
    }, 'Continue')

    await submit(page, {
        'Driver': true,
        '#driver_and_parking_parkingCost': {'No': true},
    }, 'Save and continue')
}

async function shareJourneyFlow(page, screenshot, addExtraStages)
{
    await clickLinkWithTextThatStartsWith(page, 'Share this journey')
    await screenshot('share-journey/1-intro.png')

    await submit(page, {}, 'Continue')
    await screenshot('share-journey/2-who-to-share-with.png')

    await submit(page,{
        [fixtures.DK_2_NAME]: true,
        [fixtures.DK_3_NAME]: true,
    }, 'Continue')
    await screenshot('share-journey/3-journey-purposes.png')

    await submit(page, {
        [`Purpose of journey for ${fixtures.DK_2_NAME}`]: 'To go shopping for groceries',
        [`Purpose of journey for ${fixtures.DK_3_NAME}`]: 'To go shopping for groceries',
    }, 'Continue')

    const stageTypes = addExtraStages ?
        {1: 'parking', 3: 'ticket', 4: 'ticket', 5: 'ticket', 6: 'parking'} :
        {1: 'parking', 3: 'ticket'}

    const lastStageKey = Object.keys(stageTypes).at(-1)

    const getData = (stageType) => stageType === 'walk' ?
        {} :
        {
            [`#stage_details_collection_0_${stageType}Cost`]: {'Yes': true},
            [`#stage_details_collection_1_${stageType}Cost`]: {'Yes': true},
        }

    let wizardStepNum = 3
    for(const [stageNum, stageType] of Object.entries(stageTypes)) {
        wizardStepNum += 1

        await fillForm(page, getData(stageType))
        await screenshot(`share-journey/${wizardStepNum}-details-for-stage-${stageNum}.png`)

        const buttonText = (stageNum === lastStageKey) ?
            'Save and continue' :
            'Continue'

        await submit(page, getData(stageType), buttonText) // N.B. Pre-filled
    }
}

async function returnJourneyFlow(page, screenshot, addExtraStages)
{
    await clickLinkWithTextThatStartsWith(page, 'Make a return of this journey')
    await screenshot('return-journey/1-intro.png')

    await submit(page, {}, 'Continue')
    await screenshot('return-journey/2-which-day.png')

    await submit(page, {'Day 1': true}, 'Continue')
    await screenshot('return-journey/3-times.png')

    await submit(page, {
        'What time did you start': {
            'Hour': '2',
            'Minute': '30',
            'pm': true,
        },
        'What time did you finish': {
            'Hour': '3',
            'Minute': '40',
            'pm': true,
        }
    }, 'Continue')

    const stageTypes = addExtraStages ?
        ['parking', 'ticket', 'ticket', 'ticket', 'walk', 'parking'] :
        ['ticket', 'walk', 'parking']

    const lastStageKey = stageTypes.length - 1

    const getFormData = (stageType) => stageType === 'walk' ?
        {} :
        {[`#stage_details_${stageType}Cost`]: {'Yes': true}}

    for(const [i, stageType] of stageTypes.entries()) {
        const stageNum = i + 1
        const wizardStepNum = i + 4

        await fillForm(page, getFormData(stageType))
        await screenshot(`return-journey/${wizardStepNum}-details-for-stage-${stageNum}.png`)

        const buttonText = (i === lastStageKey) ?
            'Save and continue' :
            'Continue'

        await submit(page, getFormData(stageType), buttonText) // N.B. Pre-filled
    }
}

async function repeatJourneyFlow(page, screenshot, addExtraStages)
{
    await clickLinkWithText(page, 'Repeat a journey')
    await screenshot('repeat-journey/1-intro.png')

    await submit(page, {}, 'Continue')
    await screenshot('repeat-journey/2-which-day.png')

    await submit(page, {'Day 1': true}, 'Continue')
    await screenshot('repeat-journey/3-which-journey.png')

    await submit(page, {'From Home to Portsmouth': true}, 'Continue')
    await screenshot('repeat-journey/4-purpose.png')

    await submit(page, {}, 'Continue') // N.B. Pre-filled
    await screenshot('repeat-journey/5-what-time.png')

    await submit(page, {}, 'Continue')
    await screenshot('repeat-journey/6-details-for-stage-1.png')

    await submit(page, {}, 'Continue') // N.B. Pre-filled
    await screenshot('repeat-journey/7-details-for-stage-2.png')

    await submit(page, {}, 'Continue')
    await screenshot('repeat-journey/8-details-for-stage-3.png')

    if (addExtraStages) {
        await submit(page, {}, 'Continue')
        await screenshot('repeat-journey/9-details-for-stage-4.png')

        await submit(page, {}, 'Continue')
        await screenshot('repeat-journey/10-details-for-stage-5.png')

        await submit(page, {}, 'Continue')
        await screenshot('repeat-journey/11-details-for-stage-6.png')
    }

    await submit(page, {}, 'Save and continue')
}

async function milometerReadingsFlow(page, screenshot)
{
    const isEnabled = await executeCommand(`isFeatureEnabled('milometer')`) === 'true'

    if (!isEnabled) {
        return
    }

    await clickLinkWithText(page, 'Enter/edit milometer readings for "Blue golf"')
    await screenshot('milometer/1-enter-readings.png')

    await submit(page, {
        'Miles': true,
        'Milometer reading at start of diary week': '17595',
        'Milometer reading at end of diary week': '17843',
    }, 'Save')
}

async function addJourneyForSplitterDemonstration(page, screenshot, day, sourceAndDestination, purpose)
    {
        await clickLinkWithText(page, 'View', day - 1)
        await clickLinkWithText(page, 'Add journey')

        await submit(page, {
            'Where did the journey start?': {[sourceAndDestination]: true},
            'Where did you go?': {[sourceAndDestination]: true}
        }, 'Continue')

        await submit(page, {
            'What time did you start the journey?': {
                'Hour': '8',
                'Minute': '00',
                'am': true,
            },
            'What time did you finish the journey?': {
                'Hour': '8',
                'Minute': '30',
                'am': true,
            },
        }, 'Continue')

        await submit(page, {
            'What was the main purpose of this journey?': purpose
        }, 'Save and continue')
        
        await submit(page, {}, 'Continue')

        await submit(page, {'Walk or run': true}, 'Continue')
        
        await submit(page, {
            'Distance': '1',
            'Miles': true,
            'How long did you spend travelling': '20',
            'Adults': '1',
            'Children': '0',
        }, 'Save and continue')
        
        await clickLinkWithText(page, "Alice's travel diary")
}

async function satisfactionSurveyFlow(page, screenshot)
{
    await clickLinkWithText(page, 'this short survey')
    await screenshot('satisfaction-survey/1-ease-of-use.png')

    await submit(page, {'Very easy': true}, 'Continue')

    await screenshot('satisfaction-survey/2a-diary-burden.png')
    await fillForm(page, {'Not at all burdensome': true})

    await screenshot('satisfaction-survey/2b-diary-burden_not-at-all.png')
    await fillForm(page, {
        'A little burdensome': true,
        'Other': true,
    })

    await screenshot('satisfaction-survey/2c-diary-burden_a-little-or-more.png')

    await submit(page, {
        'Not at all burdensome': true,
    }, 'Continue')

    await screenshot('satisfaction-survey/3a-devices-used.png')

    await fillForm(page, {'Other': true})
    await screenshot('satisfaction-survey/3b-devices-used_other.png')

    await submit(page, {
        'Desktop or laptop': true,
        'Other': true,
        'What device did you use?': 'Tomy toy computer',
    }, 'Continue')

    await screenshot('satisfaction-survey/4-diary-completion-patterns.png')

    await submit(page, {
        'Multiple times a day': true,
        'Yes': true,
    }, 'Continue')

    await screenshot('satisfaction-survey/5a-diary-methodologies.png')
    await fillForm(page, {'Other approach': true})
    await screenshot('satisfaction-survey/5b-diary-methodologies_other.png')

    await submit(page, {'Online questionnaire': true}, 'Save and continue')
    await screenshot('satisfaction-survey/6-questionnaire-complete.png')
}

module.exports = {
    diaryFlow: module.exports.diaryFlow,
    addCarStageFlow,
    addWalkingStageFlow,
    addPrivateOtherStageFlow,
    shareJourneyFlow,
    milometerReadingsFlow,
    satisfactionSurveyFlow,
}