# Web/UI/WebControls/TQuote

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TQuote`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TQuote.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TQuote represents the HTML `<q>` element: a short quotation inside a sentence. `Cite` is the URL of its source. A quotation of one or more paragraphs is a [TBlockQuote](./TBlockQuote.md).

The default tag is `q`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TQuote Cite="https://example.com/speech">We choose to go</com:TQuote>
```

## Key Properties

| Property | Description |
|---|---|
| `Cite` | URL of the source, from [TCiteTrait](./TCiteTrait.md); trimmed; renders `cite` when set |

## Accessibility

- `<q>` has no ARIA role.
- Browsers add the quotation marks of the element's language, so the text does not include them.
- Screen readers do not always read the generated marks.
- Browsers do not show or announce `cite`.

## Tests

- `tests/unit/Web/UI/WebControls/TQuoteTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TBlockQuote](./TBlockQuote.md)
- [TCite](./TCite.md)
- [TCiteTrait](./TCiteTrait.md)

**@since 4.4.0**
