const {submit, clickLinkWithText, getScreenshotter, fillForm, clickLinkWithTextThatStartsWith, goto} = require("../common")
const {addCarStageFlow, addPrivateOtherStageFlow, addWalkingStageFlow} = require('../diary-keeper')

// Reflow screenshots — delta on top of the legacy diary-keeper run; only captures wizard-shape divergences.
module.exports.diaryFlow = async function(page, outputDir, userIdentifier, password, addExtraStages) {
    const screenshot = await getScreenshotter(page, outputDir  + 'diary-keeper-reflow/')

    await clickLinkWithText(page, 'Sign in')
    await submit(page, {
        'Email': userIdentifier,
        'Password': password,
        'Remember me': true,
    }, 'Sign in')

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
    await screenshot('add-stage/5-add-another.png')
    await nextStage(page)
    await addWalkingStageFlow(page, screenshot)
    await nextStage(page)
    await addTrainStageFlow(page, screenshot)

    if (addExtraStages) {
        await nextStage(page)
        await addPublicOtherStageFlow(page)
        await nextStage(page)
        await addCoachStageFlow(page)
        await nextStage(page)
        await addPrivateOtherStageFlow(page)
    }

    // Final stage completed: answer "Yes" on the add-another screen to finish the journey
    await finishJourney(page)

    await clickLinkWithText(page, "Alice's travel diary")
    await clickLinkWithText(page, 'View', 1) // We want to repeat onto day 2

    // Repeat journey
    await repeatJourneyFlow(page, screenshot, addExtraStages)

    await clickLinkWithText(page, "Alice's travel diary")
    await clickLinkWithText(page, 'View', 0) // Back to day 1
    await clickLinkWithText(page, 'View', 0) // View the first (and only) journey

    // Return journey
    await returnJourneyFlow(page, screenshot, addExtraStages)

    await clickLinkWithText(page, 'Logout')
}

async function addJourneyFlow(page, screenshot) {
    await clickLinkWithText(page, 'Add journey')
    await screenshot('add-journey/1-journey-type.png')

    await submit(page, {
        'A new journey': true,
    }, 'Continue')
    await screenshot('add-journey/2-locations.png')

    await submit(page, {
        'Where did the journey start?': {
            'Home': true,
        },
        'Where did you go?': {
            'Somewhere else': true,
            'Specify where you went': 'Portsmouth',
        },
    }, 'Continue')
    await screenshot('add-journey/3-journey-details.png')

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
        'What was the main purpose of this journey?': 'To go shopping for groceries',
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

    await fillForm(page, {
        'What type of ticket did you use': 'Anytime return',
        '#ticket_details_ticketCost_ticketCost': {'Yes': true},
    })
    await screenshot('add-stage/public/3-ticket-details.png')

    await submit(page, {
        'What type of ticket did you use': 'Anytime return',
        '#ticket_details_ticketCost_ticketCost': {
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
        'What type of ticket did you use': 'Single',
        '#ticket_details_ticketCost_ticketCost': {
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
        'What type of ticket did you use': 'Single hover-pass',
        '#ticket_details_ticketCost_ticketCost': {
            'Yes': true,
            'How much did your ticket cost': '3.75',
        },
        'How many times did you board': '1',
    }, 'Save and continue')
}


async function nextStage(page) {
    await submit(page, {
        'No, I used more methods of travel before reaching Portsmouth': true,
    }, 'Continue')
}

async function finishJourney(page) {
    await submit(page, {
        'Yes, I had reached Portsmouth': true,
    }, 'Continue')
}

async function returnJourneyFlow(page, screenshot, addExtraStages)
{
    // Two entry points exist into the return flow:
    // (a) "Make a return of this journey" link on the journey view (alt-start)
    // (b) "Add journey" on the day dashboard, then picking "A return journey"
    //
    // Capture divergent pre-screens for both (1a/1b/2b), then walk path (a) end-to-end
    // to produce the shared journey-details and stage-details screenshots.

    // --- Path (b) divergent screens first (so we can return to the journey view for path (a)) ---

    const originalJourneyViewUrl = page.url()

    await clickLinkWithTextThatStartsWith(page, 'back to day')
    await clickLinkWithText(page, 'Add journey')
    await fillForm(page, {'A return journey': true})
    await screenshot('return-journey/1b-journey-type.png')
    await submit(page, {'A return journey': true}, 'Continue')

    await fillForm(page, {'Day 1': true, 'From Home to Portsmouth': true})
    await screenshot('return-journey/2b-source-day-journey.png')
    // Don't submit; navigate back to the original journey view so we can walk path (a) end-to-end
    await goto(page, originalJourneyViewUrl)

    // --- Path (a) alt-start from journey view, walked end-to-end ---

    await clickLinkWithTextThatStartsWith(page,'Make a return of this journey')
    await fillForm(page, {'Day 1': true})
    await screenshot('return-journey/1a-target-day.png')
    await submit(page, {'Day 1': true}, 'Continue')

    await fillForm(page, {
        'What time did you start': {
            'Hour': '2',
            'Minute': '30',
            'pm': true,
        },
        'What time did you finish': {
            'Hour': '3',
            'Minute': '40',
            'pm': true,
        },
        'Yes, the methods of travel are the same': true,
    })
    await screenshot('return-journey/3-journey-details.png')
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
        },
        'Yes, the methods of travel are the same': true,
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
    // Entry is now via the unified "Add journey" wizard, then picking "A repeat journey"
    await clickLinkWithText(page, 'Add journey')
    await fillForm(page, {'A repeat journey': true})
    await screenshot('repeat-journey/1-journey-type.png')
    await submit(page, {'A repeat journey': true}, 'Continue')

    await fillForm(page, {'Day 1': true, 'From Home to Portsmouth': true})
    await screenshot('repeat-journey/2-source-day-journey.png')
    await submit(page, {'Day 1': true, 'From Home to Portsmouth': true}, 'Continue')

    await fillForm(page, {'Yes, the methods of travel are the same': true})
    await screenshot('repeat-journey/3-journey-details.png')
    await submit(page, {'Yes, the methods of travel are the same': true}, 'Continue') // N.B. Times/purpose are pre-filled

    const stageCount = addExtraStages ? 6 : 3

    for (let stageNum = 1; stageNum <= stageCount; stageNum++) {
        const screenshotNum = stageNum + 3
        await screenshot(`repeat-journey/${screenshotNum}-details-for-stage-${stageNum}.png`)

        const buttonText = (stageNum === stageCount) ? 'Save and continue' : 'Continue'
        await submit(page, {}, buttonText) // N.B. Pre-filled
    }
}


