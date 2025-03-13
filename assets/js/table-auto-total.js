let lastInputWasKeyboard = false

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
    const parsedValue = getValue(value)

    cell.value = isNaN(parsedValue)
        ? value
        : (document.activeElement === cell ? parsedValue : (parsedValue.toLocaleString('en-GB')))

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
function getValue(value) {
    if (!valueSensible(value)) {
        return value
    }

    return parseInt(('' + value).replaceAll(',', '').replaceAll(' ', ''))
}

// Check whether this value should be eligible for summing and/or the addition of commas
function valueSensible(value) {
    value = '' + value

    // Firstly, the value needs to comprise only have numbers, commas and spaces
    // (optionally with leading/trailing whitespace)
    if (!value.match(/^\s*[0-9, ]*\s*$/)) {
        return false;
    }

    // Then the number (without all of the commas and spaces) needs to be a maximum of twelve digits long

    // Anything above around 15 or 16 digits, javascript seems to start rounding the numbers, and toLocaleString
    // used in addComma doesn't seem to be able to handle more than 12 digits
    const numberValue = value.replaceAll(/\s|,/g, '')
    return numberValue.length >= 0 && numberValue.length <= 12
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
            const hasChanged = getValue(value) !== getValue(cell.value)
            cell.value = value
            autoComma(cell)

            if (hasChanged) {
                cellChanged(cell, true)
            }
        }

        let total = 0
        let failure = false

        if (cell.dataset.totalSumRowsInColumn !== undefined) {
            const col = cell.dataset.col
            cell.dataset.totalSumRowsInColumn.split(',').forEach(
                function (row) {
                    if (failure) {
                        return
                    }
                    failure |= !valueSensible(cellMap[row][col].value)

                    const value = getValue(cellMap[row][col].value)
                    if (!isNaN(value)) {
                        total += value
                    }
                }
            )

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

                    const value = getValue(cellMap[row][col].value)
                    if (!isNaN(value)) {
                        total += value
                    }
                }
            })

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
