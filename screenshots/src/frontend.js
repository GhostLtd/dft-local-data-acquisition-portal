const {
    executeCommand,
    getScreenshotter,
    setupBrowserAndPage,
} = require("./common")

const {fixtures} = require("./fixtures")

const {stdin: input} = require('node:process')

const {loginFlow} = require("./frontend/login")
const {fundReturnFlow} = require("./frontend/fund-return")
const {schemeReturnFlow} = require("./frontend/scheme-return")
const {schemeManagementFlow} = require("./frontend/scheme-management");

Error.stackTraceLimit = Infinity

const frontendFlow = async function(page, baseUrl, outputDir, frontendUrl) {
    const screenshot = await getScreenshotter(page, outputDir + 'frontend/')

    await executeCommand(`screenshotsSetup('${fixtures.EMAIL_ADDRESS}')`)

    await loginFlow(page, frontendUrl, screenshot)
    await fundReturnFlow(page, frontendUrl, screenshot)
    await schemeReturnFlow(page, frontendUrl, screenshot)
    await schemeManagementFlow(page, frontendUrl, screenshot)

    // await clickLinkContainingText(page, 'Logout')
}

module.exports.run = async function (argv) {
    const {outputDir, baseUrl} = argv

    const [browser, page] = await setupBrowserAndPage()
    const version = await page.browser().version()

    console.log("Using chrome version: " + version)

    try {
        {
            await frontendFlow(page, baseUrl, outputDir, baseUrl)
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
