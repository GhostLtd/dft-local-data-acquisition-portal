const {clickLinkWithText, submit, executeCommand, clickButtonWithText} = require("../common")
const {fixtures} = require("../fixtures")

module.exports.loginFlow = async function(page, frontendUrl, screenshot) {
    await page.goto(frontendUrl)
    await screenshot('1-home-page.png')
    await clickLinkWithText(page, 'Login')

    await screenshot('2-login-page.png')

    await submit(page, {
        'Email address': fixtures.EMAIL_ADDRESS,
    }, 'Sign in')

    await screenshot('3-check-your-email.png')
    const emailLink = await executeCommand(`retrieveEmailLink('${fixtures.EMAIL_ADDRESS}')`)

    await page.goto(emailLink)
    await screenshot('4-login-link-landing-page.png')

    await clickButtonWithText(page,'Login and access dashboard')
    await screenshot('5-fund-return-list.png')
}
