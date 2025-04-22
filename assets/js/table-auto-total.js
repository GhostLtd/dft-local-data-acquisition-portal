let lastInputWasKeyboard = false
let bigDecimal = require('js-big-decimal')

const initialise = () => {
    let isEnabled = false

    const isNamedAttributeEnabled = function(attributes, name) {
        const attribute = attributes.getNamedItem(name)

        return attribute ?
            (attribute.value === '1') :
            false
    }

    const forms = document.getElementsByTagName('form')
    for (let i = 0; i < forms.length; i++) {
        const form = forms.item(i)
        const isAutoTotalEnabled = isNamedAttributeEnabled(form.attributes, 'data-auto-total')

        if (isAutoTotalEnabled) {
            const isAutoCommasEnabled = isNamedAttributeEnabled(form.attributes, 'data-auto-commas')
            initForForm(form, isAutoCommasEnabled)
            isEnabled = true;
        }
    }

    const inputs = document.getElementsByTagName('input');
    for (let i = 0; i < inputs.length; i++) {
        const input = inputs.item(i)

        if (isNamedAttributeEnabled(input.attributes, 'data-auto-commas')) {
            initAutoCommas(input)
            formatCellWithCommas(input)
            isEnabled = true
        }
    }

    if (isEnabled) {
        document.addEventListener('keydown', _ => lastInputWasKeyboard = true)
        document.addEventListener('mousedown', _ => lastInputWasKeyboard = false)
    }
}

function formatCellWithCommas(cell, setSelectionUponChange= false) {
    if (!valueSensible(cell.value)) {
        return
    }

    const value = cell.value
    const decimalValue = parseDecimalValue(value)

    if (!decimalValue) {
        cell.value = value
    } else {
        if (document.activeElement === cell) {
            cell.value = decimalValue.getValue()
        } else {
            cell.value = decimalValue.getPrettyValue(3)
        }
    }

    // Normally when a text input gets focused due to:
    // a) tabbing, the contents get selected
    // b) clicking, no selection occurs, but a caret gets put at the click location
    //
    // The autoComma routine was breaking this due to the cell value being replaced, removing the selection.
    // We use mousedown/keydown to track whether the last input event (which was the cause of the focus event)
    // was a keyboard or mouse event, and if it was a keyboard event, we can restore the selection.
    if (setSelectionUponChange && lastInputWasKeyboard && cell.value !== value) {
        cell.select()
    }
}

function initAutoCommas(cell) {
    cell.addEventListener('focus', _ => formatCellWithCommas(cell, true))
    cell.addEventListener('blur', _ => formatCellWithCommas(cell))
}

// Parse a string to retrieve its value (removing commas)
function parseDecimalValue(value) {
    try {
        let strippedValue = value.trim().replaceAll(',', '').replaceAll(' ', '')
        return new bigDecimal(strippedValue).round(2, bigDecimal.RoundingModes.HALF_UP)
    }
    catch(e) {
        return null
    }
}

// Check whether this value should be eligible for summing and/or the addition of commas
function valueSensible(value) {
    let parsedValue = parseDecimalValue(value)

    if (parsedValue === null) {
        return false
    }

    [integral, fractional] = parsedValue.getValue().split('.')
    return !(fractional.length > 2 || integral.length > 12);
}

function initForForm(form, autoCommas) {
    let cellMap
    let rowToActionMap
    let rowColToActionMap

    cellMap = {}
    rowToActionMap = {}
    rowColToActionMap = {}
    const cells = form.getElementsByTagName('input')

    // Build a map of all (type="text") cells
    for(let i=0; i<cells.length; i++) {
        const cell = cells[i]

        if (cell.attributes.getNamedItem('type').value === 'text') {
            cellMap[cell.dataset.row] ??= {}
            cellMap[cell.dataset.row][cell.dataset.col] = cell
        }
    }

    // Build a map of sumRowsInColumn and sumEntireRow cells
    // Specifically which cells or row their total depends upon
    for(let i=0; i<cells.length; i++) {
        const cell = cells[i]
        const col = cell.dataset.col
        const row = cell.dataset.row

        if (cell.dataset.totalSumRowsInColumn !== undefined && col) {
            cell.dataset.totalSumRowsInColumn.split(',').forEach(
                function (row) {
                    rowColToActionMap[row] ??= {}
                    rowColToActionMap[row][col] ??= []
                    rowColToActionMap[row][col].push(cell)
                }
            )
        }

        if (cell.dataset.totalSumEntireRow !== undefined && row) {
            rowToActionMap[row] ??= []
            rowToActionMap[row].push(cell)
        }
    }

    // Iterate all cells, updating their cell value and then hook a change listener for editable cells
    for(let i=0; i<cells.length; i++) {
        const cell = cells[i]
        if (cell.attributes.getNamedItem('type').value !== 'text') {
            continue;
        }

        updateCellTotal(cell)

        let isDisabled = (cell.attributes.getNamedItem('disabled')?.value === '1');

        if (!isDisabled) {
            cell.addEventListener('keyup', e => e.key !== 'Tab' && cellChanged(cell, false))
            cell.addEventListener('change', _ => cellChanged(cell, false))

            initAutoCommas(cell)
        }
    }

    // Add commas to the given value, if autoCommas is true
    function autoComma(cell, setSelectionUponChange= false) {
        if (!autoCommas) {
            return
        }

        formatCellWithCommas(cell, setSelectionUponChange)
    }

    // A cell has been changed. Trigger cellTotal updates for cells that depend upon its value.
    function cellChanged(cell, addCommas) {
        if (addCommas) {
            autoComma(cell)
        }

        const actionColumns = rowColToActionMap[cell.dataset.row]
        if (actionColumns) {
            const actions = actionColumns[cell.dataset.col]
            if (actions) {
                actions.forEach(updateCellTotal)
            }
        }

        const actionRow = rowToActionMap[cell.dataset.row] ?? []
        if (actionRow) {
            actionRow.forEach(updateCellTotal)
        }
    }

    // Update a cell's value
    function updateCellTotal(cell) {
        const updateCell = function(value) {
            const decimalValue = parseDecimalValue(value)
            const cellDecimalValue = parseDecimalValue(cell.value)

            let hasChanged
            if (decimalValue === null || cellDecimalValue === null) {
                hasChanged = decimalValue !== cellDecimalValue
            } else {
                hasChanged = decimalValue.compareTo(cellDecimalValue) !== 0
            }

            cell.value = value
            autoComma(cell)

            if (hasChanged) {
                cellChanged(cell, true)
            }
        }

        let total = new bigDecimal()
        let failure = false

        if (cell.dataset.totalSumRowsInColumn !== undefined) {
            const col = cell.dataset.col
            cell.dataset.totalSumRowsInColumn.split(',').forEach(
                function (row) {
                    if (failure) {
                        return
                    }
                    failure |= !valueSensible(cellMap[row][col].value)

                    const value = parseDecimalValue(cellMap[row][col].value)
                    if (value !== null) {
                        total = total.add(value)
                    }
                }
            )

            total = total.getPrettyValue(3)
            if (failure || !valueSensible(total)) {
                updateCell('Error')
            } else {
                updateCell(total)
            }
        } else if (cell.dataset.totalSumEntireRow !== undefined) {
            const row = cell.dataset.row
            const currentCol = cell.dataset.col
            Object.keys(cellMap[row]).forEach(function(col) {
                if (col !== currentCol) {
                    if (failure) {
                        return
                    }
                    failure |= !valueSensible(cellMap[row][col].value)
                    // console.log(cellMap[row][col].value + ':' + (failure ? 'Y': 'N'))

                    const value = parseDecimalValue(cellMap[row][col].value)
                    if (value !== null) {
                        total = total.add(value)
                    }
                }
            })

            total = total.getPrettyValue(3)
            if (failure || !valueSensible(total)) {
                updateCell('Error')
            } else {
                updateCell(total)
            }
        } else {
            updateCell(cell.value)
        }
    }
}

module.exports = {
    initialise
}
