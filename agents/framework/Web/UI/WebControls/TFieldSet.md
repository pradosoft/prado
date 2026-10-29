# Web/UI/WebControls/TFieldSet

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TFieldSet`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TFieldSet.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`TPanel`](./TPanel.md)

## Overview
TFieldSet represents the HTML `<fieldset>` element: a group of form controls with a caption. `Legend` renders the caption as the first child `<legend>`; a [TLegend](./TLegend.md) child renders a caption with markup or controls instead.

## Template Usage

```xml
<com:TFieldSet ID="Shipping" Legend="Shipping address">
    <com:TLabel ForControl="Street" Text="Street" />
    <com:TTextBox ID="Street" />
</com:TFieldSet>
```

## Key Properties

| Property | Description |
|---|---|
| `Legend` | Caption text; the same value as `GroupingText`. Empty renders no legend. |
| `Encode` | HTML-encodes `Legend` (from TPanel). |
| `Enabled` | False renders `disabled` and disables the child controls on the server. |
| `DefaultButton` | Enter in the group fires this button (from TPanel). |

## Rendering

| Condition | Output |
|---|---|
| No `Legend` | `<fieldset>…</fieldset>` |
| `Legend` set | `<fieldset><legend>Legend</legend>…</fieldset>`; no inner fieldset, unlike TPanel.GroupingText |
| `Enabled="false"` | `<fieldset disabled="disabled">` |

TFieldSet overrides TPanel's `renderGroupingBeginTag()` and `renderGroupingEndTag()`; TPanel's `renderLegend()` writes the legend.

## Disabled Groups

- The browser disables every control in a disabled fieldset and submits none of their values.
- A TCheckBox reads a missing value as unchecked. The child controls see the fieldset through `getEnabled(true)`, so they render `disabled`, skip post-data registration, and keep their state.
- The browser keeps controls in the first `<legend>` enabled; the server still disables them, since they are fieldset descendants.
- Enabling the fieldset in client script does not enable it on the server.

## Accessibility

- The fieldset is a `group` named by its first legend.
- A radio or check box group uses a fieldset whose legend asks the question (WCAG 1.3.1).
- A fieldset without a legend is named with `Aria.label` or `Aria.labelledby`.
- Audited in Chromium, Firefox, and WebKit: each fieldset is a `group` named by its `Legend` or TLegend; a disabled group's controls are disabled and keep their state across a postback.

## Tests

- `tests/unit/Web/UI/WebControls/TFieldSetTest.php`
- `tests/playwright/web/THtmlGroupingTestCase.spec.js` with `tests/harness/web/protected/pages/HtmlGroupingTest.page`

## See Also

- [TPanel](./TPanel.md)
- [TLegend](./TLegend.md)

**@since 4.4.0**
