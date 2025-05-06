const {
    clickLinkWithText,
    clickLinkContainingText, fetchAndClickLabelTarget, showSelectOptionsFor,
} = require("../common")

module.exports.schemeManagementFlow = async function(page, frontendUrl, screenshot) {
    await clickLinkWithText(page, 'Schemes')
    await screenshot('scheme-management/1-schemes-list.png')

    await clickLinkContainingText(page, 'view')
    await screenshot('scheme-management/2-scheme-view.png')

    await clickLinkContainingText(page, 'Edit')
    await screenshot('scheme-management/3-scheme-edit.png')

    await fetchAndClickLabelTarget(page, 'Multi-modal')
    await showSelectOptionsFor(page, 'Choose multi-modal transport mode')
    await screenshot('scheme-management/scheme-form-options/1-multi-modal.png')

    await fetchAndClickLabelTarget(page, 'Active travel')
    await showSelectOptionsFor(page, 'Choose active travel transport mode')
    await screenshot('scheme-management/scheme-form-options/2-active-travel.png')

    await fetchAndClickLabelTarget(page, 'Bus')
    await showSelectOptionsFor(page, 'Choose bus transport mode')
    await screenshot('scheme-management/scheme-form-options/3-bus.png')

    await fetchAndClickLabelTarget(page, 'Rail')
    await showSelectOptionsFor(page, 'Choose rail transport mode')
    await screenshot('scheme-management/scheme-form-options/4-rail.png')

    await fetchAndClickLabelTarget(page, 'Tram / metro / light rail')
    await showSelectOptionsFor(page, 'Choose tram / metro / light rail transport mode')
    await screenshot('scheme-management/scheme-form-options/5-tram-metro-light-rail.png')

    await fetchAndClickLabelTarget(page, 'Road / Highways maintenance')
    await showSelectOptionsFor(page, 'Choose road / highways maintenance transport mode')
    await screenshot('scheme-management/scheme-form-options/6-road-highways-maintenance.png')

    await fetchAndClickLabelTarget(page, 'Other')
    await showSelectOptionsFor(page, 'Choose other transport mode')
    await screenshot('scheme-management/scheme-form-options/7-other.png')

    await showSelectOptionsFor(page, 'Active travel element')
    await screenshot('scheme-management/scheme-form-options/8-active-travel-element.png')

    await clickLinkContainingText(page, 'Cancel')
    await clickLinkContainingText(page, 'Back')
    await clickLinkContainingText(page, 'Add')

    await screenshot('scheme-management/4-scheme-add.png')

    await clickLinkContainingText(page, 'Cancel')
    await clickLinkContainingText(page, 'view')
    await clickLinkContainingText(page, 'Delete')

    await screenshot('scheme-management/5-scheme-delete.png')
}
