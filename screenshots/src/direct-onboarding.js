const {getScreenshotter, clickButtonWithText, submit, clickLinkContainingText} = require("./common")
const {fixtures} = require("./fixtures");
module.exports.directOnboardingFlow = async function(page, outputDir, directOnboardingUrl) {
    const screenshot = await getScreenshotter(page, outputDir + 'direct/')

    await page.goto(directOnboardingUrl)
    await screenshot('0-pre-auth.png')
    await clickButtonWithText(page, 'Continue')

    await screenshot('1-check-details.png')
    await clickButtonWithText(page, 'Continue')

    await screenshot('2-logins.png')
    await submit(page, {
        [`Email address for ${fixtures.DK_1_NAME} (CAPI #1)`]: fixtures.DK_1_EMAIL_ALTERNATIVE,
    }, 'Save and continue')

    await screenshot('3a-household-member-1.png')
    await clickButtonWithText(page,'Save and continue')

    await screenshot('3b-household-member-1.png')
    await submit(page, {
        [`${fixtures.DK_1_NAME} (${fixtures.DK_1_EMAIL_ALTERNATIVE})`]: true,
    }, 'Save and continue')

    await screenshot('4-summary.png')

    await clickButtonWithText(page, 'Confirm details and submit household')

    await screenshot('5-complete.png')

    await clickLinkContainingText(page, 'Logout')
}
