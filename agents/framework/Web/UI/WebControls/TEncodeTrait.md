# Web/UI/WebControls/TEncodeTrait

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TEncodeTrait`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TEncodeTrait.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TEncodeTrait backs the `Encode` property of a control that renders a text property as HTML. One implementation serves every control with the property, so `Encode` means the same thing everywhere: off by default, and when on, the text is passed through `THttpUtility::htmlEncode()` as it is rendered.

## Methods

| Method | Description |
|---|---|
| `getEncode()` / `setEncode($value)` | The `Encode` property, kept in viewstate under `Encode`; default `false` |
| `encodeText($text)` | Protected. Returns `$text` HTML-encoded when `Encode` is true, otherwise as is. Render methods and callback client updates pass their text through it |

## Using Classes

| Class | Text the property covers |
|---|---|
| [TLabel](./TLabel.md), [TLinkButton](./TLinkButton.md), [TCheckBox](./TCheckBox.md) (and TRadioButton), [TTableCell](./TTableCell.md) (and TTableHeaderCell), [TLiteral](./TLiteral.md) | `Text` |
| [TTabView](./TTabView.md) | `Text` and `Caption` |
| [TPanel](./TPanel.md) | `GroupingText` |
| [TTable](./TTable.md), [TDataGrid](./TDataGrid.md), [TFigure](./TFigure.md) | `Caption` |
| [TDetails](./TDetails.md) | `Summary` |
| [TLiteralColumn](./TLiteralColumn.md) | `Text` and the bound data value |
| [TBaseValidator](./TBaseValidator.md) (through TLabel) | `Text` and `ErrorMessage`, in the validator, in [TValidationSummary](./TValidationSummary.md), and on the client |

The active counterparts TActiveLabel, TActiveLinkButton, TActiveCheckBox and TActiveRadioButton inherit it and encode the client update of `setText()` the same way.

## Conventions
- The using class has `getViewState()` and `setViewState()`: a `TControl`, or `TDataGridColumn`.
- The using class names the text the property covers in its class docblock, since the trait's docblocks are generic.
- `THttpUtility::htmlEncode()` translates `<`, `>` and `"` and leaves `&` alone, so encoding is idempotent; text already encoded is not double-encoded.
