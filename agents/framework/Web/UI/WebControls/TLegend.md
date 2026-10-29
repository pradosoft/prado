# Web/UI/WebControls/TLegend

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TLegend`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TLegend.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`THtmlElement`](./THtmlElement.md)

## Overview
TLegend represents the HTML `<legend>` element, the caption of its parent [TFieldSet](./TFieldSet.md). It holds markup or controls that `TFieldSet.Legend` text cannot.

## Template Usage

```xml
<com:TFieldSet>
    <com:TLegend>Contact <abbr title="required">*</abbr></com:TLegend>
    <com:TTextBox ID="Email" TextMode="Email" />
</com:TFieldSet>
```

## Rules

- TLegend is the first child of the fieldset; only the first legend names the group.
- Leave `TFieldSet.Legend` empty when a TLegend is used; both render a legend.
- The default tag is `legend`; `TagName` overrides it (from THtmlElement).

## Tests

- `tests/unit/Web/UI/WebControls/TFieldSetTest.php`

## See Also

- [TFieldSet](./TFieldSet.md)

**@since 4.4.0**
