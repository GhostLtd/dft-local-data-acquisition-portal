const {clickElementContainingText, clickButtonWithText, submit, fillForm} = require("../common")
const {fixtures} = require("../fixtures");

module.exports.schemeReturnFlow = async function(page, frontendUrl, screenshot) {
    await clickElementContainingText(page, 'a/span', fixtures.RETRAINED_SCHEME_NAME, 0, true, '/..')
    await screenshot('scheme-return/1-scheme-return.png')

    await clickElementContainingText(page, 'a/span', 'Overall', 0, true, '/..')
    await screenshot('scheme-return/2a-overall-funding.png')

    await submit(page, {
        'Is the benefit-cost ratio (BCR) for this scheme known?': {
            'Value known': true
        },
    }, 'Save')
    await screenshot('scheme-return/2b-overall-funding_validation.png')

    await submit(page, {
        'Total cost': '10876474',
        'Agreed funding': '12000000',
        'Is the benefit-cost ratio (BCR) for this scheme known?': {
            'Value known': true
        },
        'What is the benefit-cost ratio (BCR) value?': '1.25',
    }, 'Save')

    await clickElementContainingText(page, 'a/span', 'Progress', 0, true, '/..')
    await clickElementContainingText(page, 'span', 'View guidance', 0, false)
    await screenshot('scheme-return/3a-milestone-progress.png')

    await clickButtonWithText(page,'Save')
    await screenshot('scheme-return/3b-milestone-progress_validation.png')

    await submit(page, {
        'On-track rating': 'Amber',
        'Progress update': 'Planned demonstration events for vehicle successfully completed. All running on the track was completed to ensure conformal track, vibration and accoustic tests undertaken and data captured.',
        'Scheme risks': 'Freight operators do not support the project due to the impact on current and future freight services resulting in changes to project scope and delays to achieving Network Change',
    }, 'Save')

    await clickElementContainingText(page, 'a/span', 'Dates', 0, true, '/..')
    await screenshot('scheme-return/4a-milestone-dates.png')

    await clickButtonWithText(page,'Save')
    await screenshot('scheme-return/4b-milestone-dates_validation.png')

    await submit(page, {
        'Start development': {
            'Day': '1',
            'Month': '1',
            'Year': '2025',
        },
        'End development': {
            'Day': '10',
            'Month': '10',
            'Year': '2025',
        },
        'Start construction': {
            'Day': '1',
            'Month': '12',
            'Year': '2025',
        },
        'End construction': {
            'Day': '1',
            'Month': '10',
            'Year': '2027',
        },
        'Final delivery': {
            'Day': '1',
            'Month': '11',
            'Year': '2027',
        },
    }, 'Save')

    await clickElementContainingText(page, 'a/span', 'Business case', 0, true, '/..')
    await screenshot('scheme-return/5a-business-case.png')

    await fillForm(page, {
        'Current business case': {
            'N/A': true
        }
    })
    await screenshot('scheme-return/5b-business-case_not-applicable.png')

    await page.reload({waitForNavigation: true})
    await clickButtonWithText(page,'Save')
    await screenshot('scheme-return/5b-business-case_validation.png')

    await submit(page, {
        'Current business case': {
            'Working towards OBC': true,
        },
        'Expected date of approval for current business case': {
            'Day': '20',
            'Month': '1',
            'Year': '' + ((new Date).getFullYear() + 1),
        }
    }, 'Save')

    await clickElementContainingText(page, 'a', '2022/23', 0, false)
    await clickElementContainingText(page, 'a/span', '2022/23', 0, true, '/..')
    await clickElementContainingText(page, 'span', 'Comments', 0, false)
    await page.evaluate(() => window.scroll(0,0))
    await screenshot('scheme-return/6-expenses.png')

    await submit(page, {
        // Capital (CDEL) spend, CRSTS
        '#expenses_expense__2022-23__ssp__Q1': '260740',
        '#expenses_expense__2022-23__ssp__Q2': '523050',
        '#expenses_expense__2022-23__ssp__Q3': '214842',
        '#expenses_expense__2022-23__ssp__Q4': '17711',
        // Capital (CDEL) spend, all sources
        '#expenses_expense__2022-23__ssa__Q1': '600754',
        '#expenses_expense__2022-23__ssa__Q2': '124053',
        '#expenses_expense__2022-23__ssa__Q3': '916841',
        '#expenses_expense__2022-23__ssa__Q4': '764112',
        // Comments
        '#expenses_comments': 'Mysteriously higher than expected spend in Q3',
    }, 'Save')

    await clickElementContainingText(page, 'a', '2022/23', 0, false)
    await screenshot('scheme-return/7-dashboard_filled.png')
}
