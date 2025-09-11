# ExpensesTableHelper Documentation

## Overview

The `ExpensesTableHelper` is a utility class that creates logical table structures for expense forms and displays. It transforms configuration objects into table representations that can be used by forms, Twig templates, and data mappers throughout the application.

**Primary Purpose**: Generate consistent expense table structures while keeping the logic centralised in one location.

### Core Concepts

**Divisions** represent logical groupings of related columns. While commonly used for time periods (e.g., "2024-25", "2025-26"), they can represent any conceptual grouping such as departments, regions, or scenarios. Each division contains one or more columns and can be rendered independently. Tables can contain multiple divisions to show comparative data side-by-side.

**Columns** represent individual data points within a division. While often used for time intervals (e.g., "Q1", "Q2"), they can represent any data category such as budget types, cost centres, or measurement periods. Columns can be marked as forecast or actual data, affecting their styling and behaviour. When a division contains multiple columns, the helper automatically adds total columns for row summation.

**Rows** define what expense data is displayed and how it's organised. The system supports three main row types:
- **UngroupedConfiguration**: Simple flat lists of expense types without category grouping
- **CategoryConfiguration**: Groups related expense types under category headers with optional indentation
- **TotalConfiguration**: Calculated totals and subtotals that sum values from other rows

**Cells** are the intersection of rows and columns, containing the actual data values. Each cell includes metadata for form binding, accessibility, styling, and calculation logic.

## Key Components

### Core Classes

#### `ExpensesTableHelper`
**Location**: `src/Utility/ExpensesTableHelper.php`

The main utility class that orchestrates table generation.

**Key Methods**:
- `setConfiguration(TableConfiguration)` - Sets the table configuration
- `setDivisionKey(string)` - Selects which division/year to render
- `setEditableBaselines(bool)` - Controls whether baseline expense types are editable
- `getTable(): ?Table` - Generates the complete table structure
- `getRowGroupConfigurations()` - Returns the row configuration array

**Features**:
- **Caching**: Caches generated tables for performance
- **Baseline Control**: Enables/disables baseline expense types
- **Accessibility**: Generates cell titles for screen readers
- **CSS Classes**: Adds styling classes for different row types

---

### Configuration Classes

#### `TableConfiguration`
**Location**: `src/Config/ExpenseDivision/TableConfiguration.php`

Top-level configuration that defines the complete table structure.

```php
new TableConfiguration(
    $rowGroupConfigurations,    // Array of row configurations
    $divisionConfigurations,    // Array of column/division configurations
    $extraTranslationParameters // Additional translation parameters
)
```

#### `DivisionConfiguration`
**Location**: `src/Config/ExpenseDivision/DivisionConfiguration.php`

Defines column groupings (typically representing years/periods).

```php
new DivisionConfiguration(
    '2024-25',                  // Unique key for the division
    [$columnConfigurations],    // Array of ColumnConfiguration objects
    '2024-25 Label'            // Display label
)
```

**Key Methods**:
- `shouldHaveTotal(): bool` - Returns true if division has >1 column (enables total columns)
- `getColumnCount(): int` - Number of columns in this division

#### `ColumnConfiguration`
**Location**: `src/Config/ExpenseDivision/ColumnConfiguration.php`

Defines individual columns within a division.

```php
new ColumnConfiguration(
    'Q1',                       // Column key
    false,                      // Is forecast (affects styling/behavior)
    $label                      // Display label (TranslatableMessage)
)
```

---

### Row Configuration Classes

#### `UngroupedConfiguration`
**Location**: `src/Config/ExpenseRow/UngroupedConfiguration.php`

Used for simple, flat expense lists without category grouping.

```php
new UngroupedConfiguration([
    ExpenseType::FUND_CAPITAL_EXPENDITURE,
    ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE
])
```

**Use Cases**:
- Fund baselines table (`getFundBaselinesTable`)
- Scheme expenses table (`getSchemeExpensesTable`)

**Behavior**:
- Single row: Creates combined header with `colspan=2`
- Multiple rows: Creates separate row headers with "ungrouped" CSS class

#### `CategoryConfiguration`
**Location**: `src/Config/ExpenseRow/CategoryConfiguration.php`

Groups related expense types under category headers.

```php
new CategoryConfiguration(
    ExpenseCategory::FUND_CAPITAL,     // Category enum
    [                                  // Array of ExpenseType or TotalConfiguration
        ExpenseType::FUND_CAPITAL_EXPENDITURE,
        ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE,
        new TotalConfiguration(...)     // Optional subtotals
    ]
)
```

**Use Cases**:
- Fund expenses table (`getFundExpensesTable`) with multiple categories

**Behavior**:
- Single row: Creates combined category/row header
- Multiple rows: Creates group header + spacer cells for indentation

#### `TotalConfiguration`
**Location**: `src/Config/ExpenseRow/TotalConfiguration.php`

Defines calculated total/subtotal rows.

```php
new TotalConfiguration(
    'grand_total',                      // Unique key
    ['fex', 'flc', 'ftp'],             // Array of expense type keys to sum
    'Grand Total'                       // Label (string or TranslatableMessage)
)
```

**Use Cases**:
- Standalone totals (grand totals across categories)
- Subtotals within categories (subtotals within CategoryConfiguration)

**Behavior**:
- Always generates disabled (non-editable) cells
- Includes metadata for calculation logic

---

### Output Classes

#### `Table`
**Location**: `src/Config/Table/Table.php`

The final table structure containing headers and body sections.

**Key Methods**:
- `getHeadAndBodies(): array` - Returns array of TableHead and TableBody objects
- `getRows(): array` - Flattened array of all rows

#### `TableHead` / `TableBody`
**Location**: `src/Config/Table/TableHead.php`, `src/Config/Table/TableBody.php`

Container objects for table sections.

#### `Row`
**Location**: `src/Config/Table/Row.php`

Represents a table row containing cells.

**Key Methods**:
- `getCells(): array` - Returns array of Cell/Header objects

#### `Cell` / `Header`
**Location**: `src/Config/Table/Cell.php`, `src/Config/Table/Header.php`

Individual table cells with options and attributes.

**Cell Options** (for display):
- `key` - Unique identifier for form fields
- `disabled` - Whether cell should be read-only
- `text` - Accessibility title (TranslatableMessage)
- `classes` - CSS classes for styling
- `colspan`/`rowspan` - Cell spanning

**Cell Attributes** (for logic):
- `division` - Division key
- `expense_type` - ExpenseType enum
- `row_key` - String key for the expense type
- `col_key` - Column key
- `is_forecast` - Whether this is a forecast value
- `is_data_cell` - Whether this accepts user input
- `is_row_total` - Whether this is a row total cell
- `total_rows_to_sum` - Array of row keys to sum (for totals)

---

## Usage Examples

### Basic Ungrouped Table

```php
use App\Utility\ExpensesTableHelper;
use App\Config\ExpenseDivision\{TableConfiguration, DivisionConfiguration, ColumnConfiguration};
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Entity\Enum\ExpenseType;

$helper = new ExpensesTableHelper();

// Create simple single-column configuration
$configuration = new TableConfiguration(
    [new UngroupedConfiguration([ExpenseType::FUND_CAPITAL_EXPENDITURE])],
    [new DivisionConfiguration('2024-25', [
        new ColumnConfiguration('Q1', false)
    ], '2024-25')],
    []
);

$table = $helper
    ->setConfiguration($configuration)
    ->setDivisionKey('2024-25')
    ->getTable();

// Result: Table with header row + single data row
```

### Multi-Category Table with Totals

```php
// Complex table like getFundExpensesTable
$configuration = new TableConfiguration(
    [
        // Fund capital category
        new CategoryConfiguration(
            ExpenseCategory::FUND_CAPITAL,
            [
                ExpenseType::FUND_CAPITAL_EXPENDITURE,
                ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE
            ]
        ),
        // Local contributions with subtotal
        new CategoryConfiguration(
            ExpenseCategory::LOCAL_CAPITAL_CONTRIBUTIONS,
            [
                ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION,
                ExpenseType::FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION,
                new TotalConfiguration(
                    'subtotal',
                    ['flc', 'ftp'],
                    new TranslatableMessage('forms.crsts.expenses.sub_total')
                )
            ]
        ),
        // Grand total across all categories
        new TotalConfiguration(
            'grand_total',
            ['fex', 'flc', 'ftp'],
            'Grand Total'
        )
    ],
    $divisionConfigurations,
    []
);

$table = $helper
    ->setConfiguration($configuration)
    ->setDivisionKey('2024-25')
    ->getTable();

// Result: Complex table with category headers, subtotals, and grand total
```

### Multi-Year/Division Table

```php
$divisions = [
    new DivisionConfiguration('2024-25', [
        new ColumnConfiguration('Q1', false),
        new ColumnConfiguration('Q2', false)
    ], '2024-25'),
    new DivisionConfiguration('2025-26', [
        new ColumnConfiguration('Q1', true),  // Forecast
        new ColumnConfiguration('Q2', true)   // Forecast
    ], '2025-26')
];

$configuration = new TableConfiguration(
    [new UngroupedConfiguration([ExpenseType::FUND_CAPITAL_EXPENDITURE])],
    $divisions,
    []
);

// Generate table for first year
$table2024 = $helper
    ->setConfiguration($configuration)
    ->setDivisionKey('2024-25')
    ->getTable();

// Generate table for second year
$table2025 = $helper
    ->setConfiguration($configuration)
    ->setDivisionKey('2025-26')
    ->getTable();
```

### Baseline Control

```php
$configuration = new TableConfiguration(
    [new UngroupedConfiguration([ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE])],
    [$divisionConfiguration],
    []
);

// Baselines disabled (default)
$readOnlyTable = $helper
    ->setConfiguration($configuration)
    ->setDivisionKey('2024-25')
    ->setEditableBaselines(false)
    ->getTable();

// Baselines enabled
$editableTable = $helper
    ->setConfiguration($configuration)
    ->setDivisionKey('2024-25')
    ->setEditableBaselines(true)
    ->getTable();
```

---

## Real-World Usage Patterns

The ExpensesTableHelper is used throughout the application in these main patterns:

### 1. CrstsHelper Configurations

**`getFundExpensesTable()`**: Used to represent the fund expense tables.

**`getSchemeExpensesTable()`**: Used to represent the scheme expense tables.

**`getFundBaselinesTable()`**: Represents the fund baselines tables, used in the admin when surveys are in the initial state (shown as "Preparing" in the admin), to allow the CRSTS team to edit baselines before returns are released to the MCAs.

### 2. Form Integration

Tables are used with `ExpensesTable` to create Symfony forms:

```php
$table = $expensesTableHelper->getTable();
$form = $this->createForm(ExpensesTable::class, $data, [
    'table' => $table,
    'helper' => $expensesTableHelper
]);
```

### 3. Twig Templates

Tables are rendered in Twig using table macros within a proper HTML table structure:

```twig
<table class="{{ table.options.classes }}">
    {% for headOrBody in table.headAndBodies %}
        {% if headOrBody.type == 'head' %}
            {{ tableHead(headOrBody) }}
        {% else %}
            {{ tableBody(headOrBody) }}
        {% endif %}
    {% endfor %}
</table>
```
