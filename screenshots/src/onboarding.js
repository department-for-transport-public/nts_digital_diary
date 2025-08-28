const {goto, submit, clickLinkWithText, getScreenshotter, clickButtonWithText, fillForm, executeCommand} = require("./common");
const {fixtures} = require("./fixtures");

module.exports.onboardingFlow = async function(page, baseUrl, outputDir, passcode1, passcode2) {
    const screenshot = await getScreenshotter(page, outputDir  + 'onboarding/')

    await goto(page, baseUrl + 'onboarding')
    await screenshot('1-login.png')

    await submit(page, {
        'First passcode': passcode1,
        'Second passcode': passcode2,
    }, 'Sign in')
    await screenshot('2-intro.png')

    await clickButtonWithText(page, 'Continue')
    await screenshot('3-household-details.png')
    const addressNumber = 1
    const householdNumber = 2
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

    await screenshot('4a-dashboard-empty.png')

    // First diary keeper
    await clickLinkWithText(page, 'Add a household member')
    await screenshot('add-first-diary-keeper/1-add-diary-keeper.png')

    let dk1= {
        name: fixtures.DK_1_NAME,
        email: fixtures.DK_1_EMAIL,
        number: '1',
    }

    await submit(page, {
        Name: dk1.name,
        Number: dk1.number,
        Yes: true,
    }, 'Continue')
    await screenshot('add-first-diary-keeper/2a-diary-media-type.png')

    await fillForm(page, {
        'Digital diary': true
    })
    await screenshot('add-first-diary-keeper/2b-sharing-options.png')

    await submit(page, {
        'Enter their email address': dk1.email,
    }, 'Save and continue')

    await screenshot('add-first-diary-keeper/3-add-another.png')

    // Second diary keeper
    await submit(page, {
        Yes: true
    }, 'Continue')

    await screenshot('add-second-diary-keeper/1-add-diary-keeper.png')

    await addAnotherDiaryKeeper(page, screenshot, 'Bob', 2, true)
    await addAnotherDiaryKeeper(page, screenshot, 'Charlie', 3, false)

    // Vehicle
    await clickLinkWithText(page, 'Add a vehicle')
    await screenshot('add-vehicle/1-add-vehicle.png')

    await submit(page, {
        'Name': 'Blue golf',
        'Car': true,
        'Vehicle CAPI number': '1',
        [dk1.name]: true,
    }, 'Save vehicle')

    await screenshot('4b-dashboard-populated.png')

    await clickLinkWithText(page, 'Submit household')
    await screenshot('5-check-submission.png')

    await clickButtonWithText(page, 'Confirm details and submit')
    await screenshot('6-submit-confirmation.png')

    // Now visit the link from the email...
    const url = await executeCommand(`getOnboardingUrlFromEmail('${dk1.email}')`)
    await goto(page, url)
    await screenshot('7-user-password-set.png')

    // N.B. If you use too simple a password (e.g. "password", then tool breaks upon login, as Chrome will
    //      display a blocking dialog box that warning about the compromised password!
    const password = 'Test!Test!9192'

    await submit(page, {
        'Choose a password': password,
        'Repeat the password': password,
    }, 'Create account')
    await screenshot('8-done.png')

    return [dk1.email, password]
}

async function addAnotherDiaryKeeper(page, screenshot, name, number, addAnother) {
    const shouldTakeScreenshot = number === 2

    await submit(page, {
        Name: name,
        Number: `${number}`,
        No: true
    }, 'Continue')

    shouldTakeScreenshot && await screenshot('add-second-diary-keeper/2a-diary-media-type.png')

    await fillForm(page, {
        'Digital diary': true
    })

    shouldTakeScreenshot && await screenshot('add-second-diary-keeper/2b-sharing-options.png')

    await submit(page, {
        [`${fixtures.DK_1_NAME} (${fixtures.DK_1_EMAIL})`]: true
    }, 'Save and continue')

    await submit(page, {
        [addAnother ? 'Yes' : 'No']: true
    }, 'Continue')
}
