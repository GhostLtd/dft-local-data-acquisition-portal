[Home](../README.md) > Change log

# Changelog

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
