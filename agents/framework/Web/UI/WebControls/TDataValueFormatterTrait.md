# Web/UI/WebControls/TDataValueFormatterTrait

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataValueFormatterTrait`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataValueFormatterTrait.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TDataValueFormatterTrait formats bound data values. [TListControl](./TListControl.md) and [TDataGridColumn](./TDataGridColumn.md) use it, so every list control and datagrid column formats values with one implementation.

## Format Forms (`formatDataValue($format, $value)`)

| Format | Result |
|---|---|
| callable (not a function name string) | `$format($value)` |
| `''` | `TPropertyValue::ensureString($value)` |
| starts with `#` | PHP expression after `#`; `{0}` and `$value` are the data value |
| other string | `sprintf($format, $value)` |

A function name string, such as `'strtoupper'`, is a `sprintf` format.

## `#` Expressions
- The expression goes to `TComponent::evaluateExpression()` as `fn ($value) => <expression>`; the returned closure is called with the value.
- `$this` is the list control or column.
- Behaviors see the wrapped expression through `dyEvaluateExpressionFilter`.
- A parse error, or a throwable from the expression, becomes `TInvalidDataValueException` with the using class's `DATA_FORMAT_EXPRESSION_ERROR` code (`listcontrol_expression_invalid`, `datagridcolumn_expression_invalid`); the original throwable is the previous exception.
- The expression is PHP code. It comes from templates or application code and never contains request data. A callable formats without evaluating code.

## Format Properties (`getDataFormat($key)` / `setDataFormat($key, $value)`)
- A format string is kept in viewstate under `$key`.
- A callable is kept in a private array for the current request; its viewstate entry is cleared. Closures cannot be serialized into page state, so the callable is set on every request, for example in `onInit`.
- Setting a format string removes the callable.

Properties backed by these methods:

| Class | Properties |
|---|---|
| `TListControl` (and `TSuggestionList`) | `DataTextFormatString` |
| `TBoundColumn`, `TLiteralColumn` | `DataFormatString` |
| `TButtonColumn` | `DataTextFormatString`, `DataImageUrlFormatString` |
| `THyperLinkColumn` | `DataTextFormatString`, `DataNavigateUrlFormatString` |
| `TDropDownListColumn` | `DataTextFormatString`, `ListTextFormatString` (passed to its list control) |

## Using Class Contract
- Defines `DATA_FORMAT_EXPRESSION_ERROR`, the error code for an invalid `#` expression.
- Provides `getViewState()`/`setViewState()` and `evaluateExpression()` (`TControl` and `TDataGridColumn` do).

## Tests
`tests/unit/Web/UI/WebControls/TDataValueFormatterTraitTest.php`
