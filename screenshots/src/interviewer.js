const {goto, submit, clickLinkWithText, getScreenshotter, clickLinkWithTextThatStartsWith, executeCommand} = require("./common")
const {fixtures} = require("./fixtures")

module.exports.loginAndCompleteHouseholdFlow = async function(page, baseUrl, outputDir) {
    const screenshot = await getScreenshotter(page, outputDir  + 'interviewer/')

    await goto(page, baseUrl + 'login')
    await screenshot('1-login.png')

    await submit(page, {
        'Email': 'screenshots@example.com',
        'Password': 'password',
        'Remember me': true,
    }, 'Sign in')
    await screenshot('2-dashboard.png')

    await clickLinkWithText(page, 'View')
    await screenshot('3-area.png')

    await clickLinkWithText(page, 'View Set-up Codes')
    await screenshot('4-onboarding-codes.png')

    await page.goBack()
    await clickLinkWithText(page, 'View')
    await screenshot('5a-household.png')
    
    await clickLinkWithText(page, 'Impersonate')
    await screenshot('7c-impersonating-diary-keeper.png')
    await journeySplitterHomeToHomeFlow(page, screenshot)
    await journeySplitterNonHomeToNonHomeFlow(page, screenshot)
    await clickLinkWithText(page, 'Exit')

    await clickLinkWithText(page, 'View / amend diaries')
    await page.setViewport({width: 1920, height: 200})
    await screenshot('6a-compare-household-one.png')

    await clickLinkWithText(page, fixtures.DK_2_NAME, 0, false)
    await screenshot('6b-compare-household-two.png')
    await page.setViewport({width: 1003, height: 200})

    await page.goBack()
    await page.goBack()

    await clickLinkWithTextThatStartsWith(page, 'Details')
    await screenshot('7a-diary-summary.png')

    await clickLinkWithTextThatStartsWith(page, 'Mark diary as approved')
    await screenshot('8a-mark-diary-approved.png')

    const approvalData = {
        '#approve_diary_confirm_action_verifyEmptyDays': true,
    }

    await submit(page, approvalData, 'Mark diary as approved')

    // Approve second diary keeper
    await approveSubsequentDiaryKeeper(page, 1, approvalData)
    await approveSubsequentDiaryKeeper(page, 2, approvalData)

    await screenshot('5b-household-with-approved-diary-keepers.png')

    await clickLinkWithTextThatStartsWith(page, 'Details')
    await screenshot('7b-diary-keeper-approved.png')

    await clickLinkWithTextThatStartsWith(page, 'Un-approve diary')
    await screenshot('8b-unapprove-diary.png') // N.B. We don't actually unapprove it

    await clickLinkWithTextThatStartsWith(page, 'Cancel')
    await clickLinkWithTextThatStartsWith(page, 'Submit for processing')
    await screenshot('9-confirm-household-submission.png')

    await submit(page, {}, 'Confirm submission')
    await screenshot('10-household-submitted.png')

    await clickLinkWithText(page,'Logout')

}

module.exports.retrieveOnboardingCodes = async function(area) {
    return (await executeCommand(`getOnboardingCodes(${area})`)).split(',')
}

async function approveSubsequentDiaryKeeper(page, diaryKeeperIndex, approvalData)
{
    await clickLinkWithTextThatStartsWith(page, 'Details', diaryKeeperIndex)

    // Can only approve when diary is complete
    await clickLinkWithText(page, 'Impersonate')
    await clickLinkWithText(page, 'Mark travel diary as complete')
    await submit(page, {}, 'Mark travel diary as complete')
    await clickLinkWithText(page, 'Exit')
    
    await clickLinkWithTextThatStartsWith(page, 'Mark diary as approved')
    await submit(page, approvalData, 'Mark diary as approved')
}

async function journeySplitterHomeToHomeFlow(page, screenshot)
{
    await clickLinkWithText(page, 'View', 4) // Day 5
    await clickLinkWithText(page, 'View')
    await clickLinkWithText(page, 'Split journey (Interviewer only)')
    await screenshot('journey-splitter/home-to-home/1-intro.png')

    await submit(page, {}, 'Continue')
    await screenshot('journey-splitter/home-to-home/2-midpoint.png')

    await submit(page, {'Portsmouth': true}, 'Save and continue')
    await clickLinkWithText(page, 'Back to diary overview')
}

async function journeySplitterNonHomeToNonHomeFlow(page, screenshot)
{
    await clickLinkWithText(page, 'View', 5) // Day 6
    await clickLinkWithText(page, 'View')
    await clickLinkWithText(page, 'Split journey (Interviewer only)')
    await screenshot('journey-splitter/other-to-other/1-intro.png')

    await submit(page, {}, 'Continue')
    await screenshot('journey-splitter/other-to-other/2-midpoint.png')

    await submit(page, {
        'Somewhere else': true,
        'What was the location?': 'Chichester'
    }, 'Continue')
    await screenshot('journey-splitter/other-to-other/2-purpose.png')

    await submit(page,{
        'What was the purpose of the journey from Chichester to Portsmouth?': "Return to friend's house",
    }, 'Save and continue')
    await clickLinkWithText(page, 'Back to diary overview')
}
