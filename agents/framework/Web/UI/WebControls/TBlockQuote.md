# Web/UI/WebControls/TBlockQuote

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBlockQuote`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBlockQuote.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TBlockQuote represents the HTML `<blockquote>` element: a quotation of one or more paragraphs from another source. `Cite` is the URL of its source. The attribution, such as a [TCite](./TCite.md), goes outside the TBlockQuote or in a [TFigureCaption](./TFigureCaption.md) of a [TFigure](./TFigure.md) holding it.

The default tag is `blockquote`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TFigure>
	<com:TBlockQuote Cite="https://example.com/book"><p>Quoted paragraph.</p></com:TBlockQuote>
	<com:TFigureCaption><com:TCite>Book Title</com:TCite></com:TFigureCaption>
</com:TFigure>
```

## Key Properties

| Property | Description |
|---|---|
| `Cite` | URL of the source, from [TCiteTrait](./TCiteTrait.md); trimmed; renders `cite` when set |

## Accessibility

- Browsers expose `<blockquote>` with the `blockquote` role; screen readers announce its start and end.
- It is not for indenting text.
- Browsers do not show or announce `cite`.

## Tests

- `tests/unit/Web/UI/WebControls/TBlockQuoteTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TQuote](./TQuote.md)
- [TCite](./TCite.md)
- [TCiteTrait](./TCiteTrait.md)
- [TFigure](./TFigure.md)

**@since 4.4.0**
