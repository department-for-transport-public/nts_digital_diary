const {
    executeCommand,
    getScreenshotter,
    setupBrowserAndPage,
} = require("./common")

const {diaryFlow} = require("./diary-keeper")
const {directOnboardingFlow} = require("./direct-onboarding")
const {documentationFlow} = require("./documentation")
const {loginAndCompleteHouseholdFlow, retrieveOnboardingCodes} = require("./interviewer")
const {onboardingFlow} = require('./onboarding')

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
                await directOnboardingFlow(page, baseUrl, outputDir, directOnboardingUrl)
            }

            {
                const [passcode1, passcode2] = await retrieveOnboardingCodes(111111)
                const [userIdentifier, password] = await onboardingFlow(page, baseUrl, outputDir, passcode1, passcode2)

                await diaryFlow(page, baseUrl, outputDir, userIdentifier, password, extraDiaryStages)
                await loginAndCompleteHouseholdFlow(page, baseUrl, outputDir)
            }
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
