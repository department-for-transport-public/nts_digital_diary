const {
    executeCommand,
    getScreenshotter,
    setupBrowserAndPage,
} = require("./common")

const {accountSetupFlow} = require("./account-setup")
const {directOnboardingFlow} = require("./direct-onboarding")
const {documentationFlow} = require("./documentation")
const {loginAndCompleteHouseholdFlow, retrieveOnboardingCodes} = require("./interviewer")
const {onboardingFlow} = require('./onboarding')
const {fixtures} = require('./fixtures')

const {stdin: input} = require('node:process')

Error.stackTraceLimit = Infinity

module.exports.run = async function (argv) {
    const {outputDir, baseUrl, documentation, extraDiaryStages} = argv

    const [browser, page] = await setupBrowserAndPage()
    const version = await page.browser().version()

    console.log("Using Chrome version : " + version)

    try {
        if (!documentation) {
            {
                const [passcode1] = await retrieveOnboardingCodes(111111)
                const directOnboardingUrl = await executeCommand(`getDirectOnboardingUrl('${passcode1}', 2, 2, 1)`)
                await directOnboardingFlow(page, outputDir, directOnboardingUrl)
            }

            {
                const [userIdentifier, password] = await onboardingFlow(page, baseUrl, outputDir)
                const {diaryFlow} = require('./diary-keeper')
                await diaryFlow(page, outputDir, userIdentifier, password, extraDiaryStages)
            }

            {
                // Reflow — pre-seeded household, log in directly
                const {diaryFlow} = require('./reflow/diary-keeper')
                await diaryFlow(page, outputDir, fixtures.REFLOW_DK_1_EMAIL, fixtures.REFLOW_DK_1_PASSWORD, extraDiaryStages)
            }
            await loginAndCompleteHouseholdFlow(page, baseUrl, outputDir)
            await accountSetupFlow(page, baseUrl, outputDir)
        }

        if (documentation) {
            await documentationFlow(page, baseUrl, outputDir)
        }
    } catch (e) {
        console.log(e.stack)
        const screenshot = await getScreenshotter(page, outputDir)
        await screenshot('error.png')
        console.log('Image generated: error.png')
    }

    input.destroy()
    await browser.close()
}
