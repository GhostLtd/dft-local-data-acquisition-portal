const initialise = () => {
    const forms = document.getElementsByTagName('form')
    for (let i = 0; i < forms.length; i++) {
        const form = forms.item(i)
        const dataAutoTotal = form.attributes.getNamedItem('data-auto-total')

        if (dataAutoTotal && dataAutoTotal.value === '1') {
            const autoCommas = form.attributes.getNamedItem('data-auto-commas').value === '1'
            initForForm(form, autoCommas)
        }
    }
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
            cell.addEventListener('change', _ => cellChanged(cell, true))
            cell.addEventListener('keyup', e => e.key !== 'Tab' && cellChanged(cell, false))
        }
    }

    // Add commas to the given value, if autoCommas is true
    function autoComma(value) {
        if (!autoCommas) {
            return value
        }

        const parsedValue = getValue(value)
        return isNaN(parsedValue) ? value : parsedValue.toLocaleString('en-GB')
    }

    // Parse a string to retrieve its value (removing commas)
    function getValue(value) {
        return parseInt(('' + value).replaceAll(',', ''))
    }

    // A cell has been changed. Trigger cellTotal updates for cells that depend upon its value.
    function cellChanged(cell, addCommas) {
        if (addCommas) {
            cell.value = autoComma(cell.value)
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
            cell.value = autoComma(value)

            if (hasChanged) {
                cellChanged(cell, true)
            }
        }

        let total = 0

        if (cell.dataset.totalSumRowsInColumn !== undefined) {
            const col = cell.dataset.col
            cell.dataset.totalSumRowsInColumn.split(',').forEach(
                function (row) {
                    const value = getValue(cellMap[row][col].value)
                    if (!isNaN(value)) {
                        total += value
                    }
                }
            )

            updateCell(total)
        } else if (cell.dataset.totalSumEntireRow !== undefined) {
            const row = cell.dataset.row
            const currentCol = cell.dataset.col
            Object.keys(cellMap[row]).forEach(function(col) {
                if (col !== currentCol) {
                    const value = getValue(cellMap[row][col].value)
                    if (!isNaN(value)) {
                        total += value
                    }
                }
            })

            updateCell(total)
        } else {
            updateCell(cell.value)
        }
    }
}

module.exports = {
    initialise
}
