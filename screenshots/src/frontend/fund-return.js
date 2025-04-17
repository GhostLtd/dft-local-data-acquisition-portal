const {clickLinkContainingText, clickElementContainingText, clickButtonWithText, submit} = require("../common")

module.exports.fundReturnFlow = async function(page, frontendUrl, screenshot) {
    await clickLinkContainingText(page, 'CRSTS')
    await screenshot('fund-return/1-fund-return.png')

    await clickElementContainingText(page, 'a/span', 'Overall', 0, true, '/..')
    await clickElementContainingText(page, 'span', 'View guidance', 0, false)
    await screenshot('fund-return/2a-overall-progress.png')

    await clickButtonWithText(page,'Save')
    await screenshot('fund-return/2b-overall-progress_validation.png')

    await submit(page, {
        'Overall progress summary': 'The project is going well',
        'Overall delivery confidence': 'The project will be delivered on time',
        'Overall confidence rating': {'Green': true},
    }, 'Save')

    await clickElementContainingText(page, 'a/span', 'Funding', 0, true, '/..')
    await screenshot('fund-return/3a-local-contribution.png')

    await clickButtonWithText(page,'Save')
    await screenshot('fund-return/3b-local_contribution_validation.png')

    await submit(page, {
        'Local contribution': '£7.4m in 2022/23 and £8.3m in 2023/25, funded by borrowing',
        'Resource (RDEL) funding' : 'Majority of CRSTS-funded schemes have achieved SOBC',
    }, 'Save')

    await clickElementContainingText(page, 'a/span', 'Misc', 0, true, '/..')
    await screenshot('fund-return/4-miscellaneous.png')

    await submit(page, {
        'Change control / comments': 'No comments',
    }, 'Save')

    await clickElementContainingText(page, 'a', '2022/23', 0, false)
    await clickElementContainingText(page, 'a/span', '2022/23', 0, true, '/..')
    await clickElementContainingText(page, 'span', 'Comments', 0, false)
    await page.evaluate(() => window.scroll(0,0))
    await screenshot('fund-return/5-expenses.png')

    await submit(page, {
        // CRSTS expenditure
        '#expenses_expense__2022-23__fex__Q1': '4600750',
        '#expenses_expense__2022-23__fex__Q2': '5123050',
        '#expenses_expense__2022-23__fex__Q3': '2914842',
        '#expenses_expense__2022-23__fex__Q4': '1767111',
        // CRSTS expenditure including over-programming
        '#expenses_expense__2022-23__fop__Q1': '4600750',
        '#expenses_expense__2022-23__fop__Q2': '5123050',
        '#expenses_expense__2022-23__fop__Q3': '2914842',
        '#expenses_expense__2022-23__fop__Q4': '1767111',
        // Local capital contributions: MCA/LA
        '#expenses_expense__2022-23__flc__Q1': '128000',
        '#expenses_expense__2022-23__flc__Q2': '76000',
        '#expenses_expense__2022-23__flc__Q3': '48000',
        '#expenses_expense__2022-23__flc__Q4': '65000',
        // Local capital contributions: 3rd party
        '#expenses_expense__2022-23__ftp__Q1': '81000',
        '#expenses_expense__2022-23__ftp__Q2': '308250',
        '#expenses_expense__2022-23__ftp__Q3': '74000',
        '#expenses_expense__2022-23__ftp__Q4': '115866',
        // Other capital contributions
        '#expenses_expense__2022-23__fot__Q1': '12274939',
        '#expenses_expense__2022-23__fot__Q2': '22384278',
        '#expenses_expense__2022-23__fot__Q3': '38373100',
        '#expenses_expense__2022-23__fot__Q4': '20584704',
        // CRSTS resource
        '#expenses_expense__2022-23__fre__Q1': '118292',
        '#expenses_expense__2022-23__fre__Q2': '256988',
        '#expenses_expense__2022-23__fre__Q3': '638202',
        '#expenses_expense__2022-23__fre__Q4': '672287',
    }, 'Save')

    await screenshot('fund-return/6-dashboard_filled.png')
}
