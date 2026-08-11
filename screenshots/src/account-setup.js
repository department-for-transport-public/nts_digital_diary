const {goto, getScreenshotter, clickButtonWithText, executeCommand, submit} = require("./common")
const {fixtures} = require("./fixtures")

const PASSWORD = 'Test!Test!9192'

async function visitAccountSetup(page, email, expiry) {
    const url = await executeCommand(`getAccountSetupUrl('${email}', '${expiry}')`)
    await goto(page, url)
}

async function logoutAndVisit(page, baseUrl, email, expiry) {
    await goto(page, baseUrl + 'logout')
    await visitAccountSetup(page, email, expiry)
}

module.exports.accountSetupFlow = async function(page, baseUrl, outputDir) {
    const screenshot = await getScreenshotter(page, outputDir + 'account-setup/')

    const dkA = fixtures.ACCOUNT_SETUP_DK_A_EMAIL
    const dkB = fixtures.ACCOUNT_SETUP_DK_B_EMAIL
    const ivA = fixtures.ACCOUNT_SETUP_IV_A_EMAIL
    const ivB = fixtures.ACCOUNT_SETUP_IV_B_EMAIL

    // Shared pre-auth screenshot (use DK A's valid link)
    await visitAccountSetup(page, dkA, 'valid')
    await screenshot('1-pre-auth.png')

    // DK A: no-password + valid link -> password form -> auto-login
    await clickButtonWithText(page, 'Continue')
    await screenshot('diary-keeper/without-password/1a-set-password.png')

    await submit(page, {
        'Choose a password': PASSWORD,
        'Repeat the password': PASSWORD,
    }, 'Create account')
    await screenshot('diary-keeper/without-password/2-complete.png')

    // DK A: with-password + valid link -> auto-login to /travel-diary
    await logoutAndVisit(page, baseUrl, dkA, 'valid')
    await clickButtonWithText(page, 'Continue')
    await screenshot('diary-keeper/with-password/1a-valid.png')

    // DK A: with-password + expired link -> expired page
    await logoutAndVisit(page, baseUrl, dkA, 'expired')
    await clickButtonWithText(page, 'Continue')
    await screenshot('diary-keeper/with-password/1b-expired.png')

    // DK B: no-password + expired link -> expired page
    await logoutAndVisit(page, baseUrl, dkB, 'expired')
    await clickButtonWithText(page, 'Continue')
    await screenshot('diary-keeper/without-password/1b-expired.png')

    // IV A: no-password + valid link -> password form -> redirect to /login
    await logoutAndVisit(page, baseUrl, ivA, 'valid')
    await clickButtonWithText(page, 'Continue')
    await screenshot('interviewer/without-password/1a-set-password.png')

    await submit(page, {
        'Choose a password': PASSWORD,
        'Repeat the password': PASSWORD,
    }, 'Create account')
    await screenshot('interviewer/without-password/2-complete.png')

    // IV A: with-password + valid link -> redirect to /login
    await logoutAndVisit(page, baseUrl, ivA, 'valid')
    await clickButtonWithText(page, 'Continue')
    await screenshot('interviewer/with-password/1a-valid.png')

    // IV A: with-password + expired link -> redirect to /login (no email resent)
    await logoutAndVisit(page, baseUrl, ivA, 'expired')
    await clickButtonWithText(page, 'Continue')
    await screenshot('interviewer/with-password/1b-expired.png')

    // IV B: no-password + expired link -> expired page (email resent)
    await logoutAndVisit(page, baseUrl, ivB, 'expired')
    await clickButtonWithText(page, 'Continue')
    await screenshot('interviewer/without-password/1b-expired.png')

    await goto(page, baseUrl + 'logout')
}
