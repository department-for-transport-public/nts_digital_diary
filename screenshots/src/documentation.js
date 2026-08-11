const {goto, submit, clickLinkWithText, getScreenshotter, clickButtonWithText, fillForm, executeCommand,
    clickLinkWithTextThatStartsWith, clickLinkContainingText, clickElement, waitForOptions, VIEWPORTS
} = require("./common")
const {retrieveOnboardingCodes} = require("./interviewer")

const nameEmailTemplate = "{name} ({email})"
const dk1 = {
    name: 'Alice',
    email: 'alice@example.com',
    number: '1',
}
const dk2 = {
    name: 'Bob',
    email: 'bob@example.com',
    number: '2',
}
const dk3 = {
    name: 'Charlie',
    newEmail: 'charlie2@example.com',
    number: '3',
}
const dk4 = {
    name: 'David',
    number: '4',
}


module.exports.documentationFlow = async function(page, baseUrl, outputDir) {
    const screenshot = await getScreenshotter(page, outputDir  + 'documentation/')
    await page.setViewport(VIEWPORTS.desktop)

    const [passcode1, passcode2] = await retrieveOnboardingCodes(111111)
    await onboardingFlow(page, baseUrl, screenshot, passcode1, passcode2)

    await executeCommand(`addDocumentationFixtures()`)
    await shareDkTwosJourneys(page)

    await interviewerFlow(page, baseUrl, screenshot)
}

async function onboardingFlow(page, baseUrl, screenshot, passcode1, passcode2) {
    await goto(page, baseUrl + 'onboarding')

    await submit(page, {
        'First passcode': passcode1,
        'Second passcode': passcode2,
    }, 'Sign in')

    await screenshot('1-setting-up-the-household.png')
    
    await clickButtonWithText(page, 'Continue')
    await screenshot('2-household-details.png')
    
    const addressNumber = 2
    const householdNumber = 3
    const now = new Date()
    // we actually want a date in the following month. JS will roll the year over for us if need be.
    now.setMonth(now.getMonth() + 1)

    await submit(page, {
        CL: await executeCommand(`getCheckLetter(111111, ${addressNumber}, ${householdNumber})`),
        Add: `${addressNumber}`,
        H: `${householdNumber}`,
        Day: '2',
        Month: `${(now.getMonth() + 1)}`, // JS months are 0 indexed, but the form is expecting 1 based index for month
        Year: `${now.getFullYear()}`,
    }, 'Save and continue')

    await screenshot('3-household-summary.png')

    // First diary keeper
    await clickLinkWithText(page, 'Add a household member')
    await screenshot('4-add-householder_details.png')

    await submit(page, {
        Name: dk1.name,
        Number: dk1.number,
        Yes: true,
    }, 'Continue')
    await screenshot('4-add-householder_identity.png')

    await fillForm(page, {
        'Digital diary': true
    })
    await screenshot('5-add-householder_identity-digital.png')

    await submit(page, {
        'Enter their email address': dk1.email,
    }, 'Save and continue')

    await screenshot('6-add-another.png')

    // Second diary keeper
    await submit(page, {
        Yes: true
    }, 'Continue')

    await submit(page, {
        Name: dk2.name,
        Number: dk2.number,
        Yes: true,
    }, 'Continue')

    await fillForm(page, {
        'Digital diary': true
    })
    await screenshot('7-add-second-householder_identity.png')

    await fillForm(page, {
        'Enter their email address': dk2.email,
        [substitute(nameEmailTemplate, dk1)]: true
    })
    await screenshot('8-add-second-householder_identity-and-proxy.png',{
        cropSelector: 'fieldset.govuk-fieldset[aria-describedby="user_identifier_user_proxies-hint"]',
        cropPadding: 20,
    })

    await submit(page, {}, 'Save and continue')

    await submit(page, {
        Yes: true
    }, 'Continue')
    
    // Third diary keeper
    await submit(page, {
        Yes: true
    }, 'Continue')

    await submit(page, {
        Name: dk3.name,
        Number: dk3.number,
        No: true,
    }, 'Continue')

    await fillForm(page, {
        'Digital diary': true,
    })
    
    await screenshot('9-add-third-householder_identity-and-proxy.png',{
        cropSelector: '#user_identifier',
        cropPadding: 20,
    })

    await submit(page, {
        [substitute(nameEmailTemplate, dk2)]: true,
        [substitute(nameEmailTemplate, dk1)]: true,
    }, 'Save and continue')

    // Fourth diary keeper
    await submit(page, {
        Yes: true
    }, 'Continue')

    await submit(page, {
        Name: dk4.name,
        Number: dk4.number,
        No: true,
    }, 'Continue')

    await fillForm(page, {
        'Digital diary': true,
        [substitute(nameEmailTemplate, dk2)]: true,
        [substitute(nameEmailTemplate, dk1)]: true,
    })

    await screenshot('10-add-forth-householder_identity-and-proxy.png',{
        cropSelector: 'fieldset.govuk-fieldset[aria-describedby="user_identifier_user_proxies-hint"]',
        cropPadding: 20,
    })

    await submit(page, {}, 'Save and continue')

    await fillForm(page, {
        No: true
    })

    await screenshot('11-add-another_no.png',{
        cropSelector: ['h1.govuk-heading-l', 'form'],
        cropPadding: 20,
    })

    await submit(page, {
        No: true
    }, 'Continue')

    await screenshot('12-summary-page.png')

    // Vehicle
    await clickLinkWithText(page, 'Add a vehicle')
    await screenshot('13-add-vehicle.png')

    await submit(page, {
        'Name': `${dk1.name}'s White Volvo`,
        'Car': true,
        'Vehicle CAPI number': '1',
        [dk1.name]: true,
    }, 'Save vehicle')

    await clickLinkWithText(page, 'Add a vehicle')
    await submit(page, {
        'Name': `${dk2.name}'s Red Fiat`,
        'Car': true,
        'Vehicle CAPI number': '2',
        [dk2.name]: true,
    }, 'Save vehicle')

    await screenshot('14-summary-page.png')

    await clickLinkWithText(page, 'Submit household')
    await fillForm(page, {
        'Enable journey sharing for the household': true,
    })
    await screenshot('15-submission-page.png',{
        cropSelector: '.govuk-checkboxes__item',
        cropPadding: 20,
    })
    await screenshot('16-submission-page.png')

    await clickButtonWithText(page, 'Confirm details and submit')
    await screenshot('17-confirmation-page.png')

    await clickLinkWithTextThatStartsWith(page, 'Logout')
}

async function interviewerFlow(page, baseUrl, screenshot) {
    await goto(page, baseUrl + 'login')

    await submit(page, {
        'Email': 'screenshots@example.com',
        'Password': 'password',
        'Remember me': true,
    }, 'Sign in')

    await clickLinkWithText(page, 'View')
    await screenshot('18-area-view.png')


    const viewHousehold = await page.$("::-p-xpath(//tr/th[text() = '111111 / 02 / 3']/../td/a)")
    await clickElement(page, viewHousehold)
    await screenshot('19-household-view.png')

    await screenshot('20-changing-emails-and-proxies.png', {
        cropSelector: '.govuk-body + .govuk-table .govuk-table__body tr:nth-child(3)',
        cropPadding: 10
    })
    await clickLinkWithText(page, 'Details / actions', 2)
    await screenshot('21-details-for-dk-three.png')

    await clickLinkWithText(page, 'Change email')
    await screenshot('22-change-email-address.png')

    await submit(page,{
        'New email address': dk3.newEmail,
    }, 'Change email address')
    await screenshot('23-change-initiated.png')

    await clickLinkWithText(page, 'Back to household')
    await screenshot(`24-dk-one.png`, {
        cropSelector: '.govuk-body + .govuk-table .govuk-table__body tr:nth-child(1)',
        cropPadding: 10
    })

    await clickLinkWithText(page, 'Details / actions')
    await screenshot('25-details-for-dk-one.png')

    await clickLinkWithText(page, 'Change')
    await screenshot('26-change-proxies.png')

    await fillForm(page, {[dk2.name]: true})
    await screenshot('27-ticking-boxes.png', {
        cropSelector: ['main > h1', 'main > form'],
        cropPadding: 10
    })
    await submit(page, {}, 'Save')

    await clickLinkWithText(page, 'Back to household')

    const button = await page.waitForSelector("::-p-xpath(//a[contains(text(), 'View / amend')])")
    await button.evaluate(b => b.classList.add('highlight'))

    await page.setViewport(VIEWPORTS.wide)
    await screenshot('28-amend-a-travel-diary.png')

    await clickLinkContainingText(page, 'View / amend')
    await screenshot('29-compare-household-view.png')

    await clickLinkWithText(page, dk2.name)
    await screenshot('30-compare-household-view_dk-two.png')

    const two = await page.waitForSelector("::-p-xpath(//div[@id='tab-diary-keeper-2']//a[text()='2'])")
    await two.evaluate(t => t.classList.add('highlight'))

    await screenshot('31-clicking-journey-number.png', {
        cropSelector: [
            '#tab-diary-keeper-2 .govuk-table__head',
            '#tab-diary-keeper-2 .govuk-table__body > tr:nth-child(3)',
        ],
        cropPadding: {left: 20, right: 20},
        cropX2: 820,
    })

    await two.evaluate(t => t.classList.remove('highlight'))
    const impersonate = await page.waitForSelector("::-p-xpath(//div[@id='tab-diary-keeper-2']//a[starts-with(text(),'Impersonate')])")
    await impersonate.evaluate(t => t.classList.add('highlight'))

    await screenshot('32-impersonation.png', {
        cropSelector: [
            '.govuk-heading-l',
            '#tab-diary-keeper-2 .govuk-table__head',
            '#tab-diary-keeper-2 .govuk-table__body > tr:nth-child(1)',
        ],
        cropPadding: {left: 20, right: 20, top: 20},
        cropX2: 820,
    })

    await page.setViewport(VIEWPORTS.desktop)

    await clickLinkWithText(page, dk1.name)
    await clickLinkContainingText(page, 'Impersonate')

    await screenshot('33-impersonating-dk-one.png', {
        cropSelector: [
            '.govuk-header',
            '.govuk-summary-list__key:nth-child(1)',
        ],
        cropPadding: {bottom: 2} // To get the border in the summary list
    })

    await clickLinkContainingText(page, 'Exit')
    await clickLinkContainingText(page, dk2.name)
    await clickLinkContainingText(page, 'Impersonate', 1) // Need the 1, since the 1st link is the hidden one on dk-one's tab
    await clickLinkContainingText(page, 'Back to diary')

    await screenshot('34-impersonating-dk-two.png')

    await clickLinkContainingText(page, 'View', 1)
    await screenshot('35-day-view.png')

    await clickLinkContainingText(page, 'View')
    await screenshot('36-journey-view.png')

    await clickLinkContainingText(page, 'Exit')

    await page.setViewport(VIEWPORTS.wide)
    await page.$$eval(
        "::-p-xpath(//div[@id='tab-diary-keeper-1']//tbody/tr[2]/td[contains(text(), 'Holt')])",
        elements => elements.map(e => e.innerHTML = '<span class="highlight">' + e.innerHTML + '</span>')
    );

    await screenshot('37-splitting-round-trips.png') // This one

    const linkToJourneyTwo = await page.$("::-p-xpath(//div[@id='tab-diary-keeper-1']//tbody/tr[2]/th/a)")
    await clickElement(page, linkToJourneyTwo)

    await page.setViewport(VIEWPORTS.desktop)
    await page.$$eval(
        "::-p-xpath(//a[contains(text(), 'Split journey')])",
        elements => elements.map(e => e.classList.add('highlight'))
    )
    await screenshot('38-splitting-round-trips.png')

    await clickLinkContainingText(page, 'Split journey')

    await screenshot('39-split-dk-one-journey.png', {
        cropSelector: [
            '.govuk-header',
            'form',
        ]
    })

    await submit(page, {}, 'Continue')
    await fillForm(page, {'Somewhere else': true, 'What was the location?': 'Station road retail park, Holt'})
    await screenshot('40-midpoint.png', {
        cropSelector: [
            '.govuk-header',
            'form',
        ]
    })

    await page.goto(baseUrl + 'travel-diary')
    await clickLinkContainingText(page, 'Mark my travel diary as complete')
    await submit(page, {}, 'Mark my travel diary as complete')
    await clickLinkContainingText(page, 'Exit')
    await clickLinkContainingText(page, '2 / 3')
    await clickLinkContainingText(page, 'Details')

    await screenshot('41-approving-a-diary.png')

    await clickLinkContainingText(page, 'Mark diary as approved')
    await screenshot('42-mark-diary-as-approved.png')

    await submit(page, {}, 'Mark diary as approved')
    await screenshot('43-approved.png', {
        cropSelector: ['.govuk-notification-banner--success'],
        cropPadding: 20,
    })

    await executeCommand("approveAllDiaries(111111,2,3)")
    await page.reload(waitForOptions)

    await screenshot('44-submitting-a-household.png')

    await clickLinkContainingText(page, 'Submit for processing')
    await screenshot('45-submit-household.png')
}

async function shareDkTwosJourneys(page){
    const dkUrl = await executeCommand(`getOnboardingUrlFromEmail('${dk2.email}')`);
    await goto(page, dkUrl)
    const password = 'DKtwo9832'
    await clickButtonWithText(page, 'Continue') // pre-auth
    await submit(page, {'Choose a password': password, 'Repeat the password': password}, 'Create account')
    await submit(page, {'12 hour': true}, 'Save')

    await clickLinkContainingText(page, 'View', 1) // Day 1 (N.B. practise day is 0)
    await clickLinkContainingText(page, 'View', 0) // Journey 1
    await clickLinkContainingText(page, 'Share this journey')
    await submit(page, {}, 'Continue')
    await submit(page, {[dk3.name]: true, [dk4.name]: true}, 'Continue')
    await submit(page, {
        [`Purpose of journey for ${dk3.name}`]: 'To go to school',
        [`Purpose of journey for ${dk4.name}`]: 'To go to school',
    }, 'Continue')
    await submit(page, {
        [`Did ${dk3.name} pay anything for parking?`]: {No: true},
        [`Did ${dk4.name} pay anything for parking?`]: {No: true},
    }, 'Save and continue')
    await clickLinkContainingText(page, 'Back to day')

    await clickLinkContainingText(page, 'View', 3) // Journey 4
    await clickLinkContainingText(page, 'Share this journey')
    await submit(page, {}, 'Continue')
    await submit(page, {[dk3.name]: true, [dk4.name]: true}, 'Continue')
    await submit(page, {}, 'Continue') // pre-filled with "Go home"
    await submit(page, {
        [`Did ${dk3.name} pay anything for parking?`]: {No: true},
        [`Did ${dk4.name} pay anything for parking?`]: {No: true},
    }, 'Save and continue')

    await clickLinkContainingText(page, 'Logout')
}

function substitute(template, variables) {
    return template.replace(/{(\w+)}/g, (match, key) => variables[key] || match);
}