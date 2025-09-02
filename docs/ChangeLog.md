[Home](../README.md) > Change log

# Changelog

## Upcoming

- **Feature**: Add the ability to add fund awards to an authority
- **Feature**: Add privacy statement
- **Update**: Allow spreadsheet export prior to submission
- **Update**: Enable the cron job for the creation of new returns
- **Update**: Pre + post install scripts enabled in cloudbuild.yaml pipeline
- **Update**: Add help text for the Final Delivery milestone date
- **Fix**: Fix some GovUK rebrand styles not showing up 
- **Fix**: Remove legacy database env bind bindings
- **Fix**: Maintenance mode not immediately enabling/disabling due to cache problem

## 5th August 2025

- **Feature**: Ability to export a return as an excel file
- **Update**: Don't add sample fixtures when adding a new MCA
- **Update**: Order admin MCA user list (admin first, otherwise ordered by name)

## 7th July 2025

- **Fix**: Update export_scheme_return_data (i.e. export view) to use the property change log for scheme-related details
- **Update**: Add is_new_scheme field to export_scheme_return_data
- **Update**: Import some missing scheme historical data
- **Update**: Only allow re-opening of the latest return

## 25th June 2025

- **Feature**: Add pre-signoff screen flagging validation issues that need to be fixed with the return
- **Feature**: Allow the editing of baselines prior to the release of the returns
- **Update**: Disallow editing of schemes that were merged/split/completed in a previous return
- **Update**: Make sure non-RAG on-track ratings (e.g. split, cancelled) are propagated to future returns
- **Update**: Rename "Cancelled" to "Cancelled / on hold"
- **Fix**: Business date validation fixes
