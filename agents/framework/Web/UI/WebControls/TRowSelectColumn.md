# Web/UI/WebControls/TRowSelectColumn

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRowSelectColumn`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRowSelectColumn.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRowSelectColumn (@since 4.4.0, #1407) is a `TDataGrid` column of row checkboxes with a select-all checkbox in the header cell, after Drupal's tableselect.

```html
<com:TDataGrid ID="Grid" DataKeyField="id" AutoGenerateColumns="false">
    <com:TRowSelectColumn ID="Select" DataTextField="name" />
    <com:TBoundColumn DataField="name" HeaderText="Name" />
</com:TDataGrid>
```

## Key Properties/Methods

- `DataTextField` - Field whose value names each row checkbox: "Select {value}". Empty: "Select row N" (one-based item index)
- `SelectAllText` - Name of the select-all checkbox (default '' → "Select all rows")
- `ShowSelectAll` - Header cell holds the select-all checkbox (default true); false shows `HeaderText`
- `getRowCheckBoxes()` - Row `TCheckBox` controls keyed by item index
- `getSelectedIndices()` - Item indices of the checked rows
- `getSelectedKeys()` - `DataKeys` entries of the checked rows (data source keys when `DataKeyField` is empty)

## Implementation

- `initializeCell()` records the column's cell index and registers each checkbox on its cell as `CheckBox`; the cells are rebuilt on every request, so the checked state posts back as usual.
- The names are `aria-label` on the `<input>` through `TCheckBox::getInputAttributes()` (TCheckBox renders no ARIA collection). Row names are set in `OnDataBinding` and persist in viewstate. All names pass through `Prado::localize()`.
- The select-all checkbox's `OnPreRender` registers the `rowselect` script package and `Prado.WebUI.TRowSelectColumn` with `ID` (select-all ClientID) and `CheckBoxIDs`.
- Client (`controls/rowselect.js`): select-all checks/clears enabled rows; the select-all shows checked when all rows are checked and `indeterminate` when some are; a shift-click sets the enabled rows between the last clicked row and the clicked row to its state. Without script, the row checkboxes work alone.

## See Also

- [TDataGrid](./TDataGrid.md)
- [TCheckBoxColumn](./TCheckBoxColumn.md)
