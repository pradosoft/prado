# Web/UI/WebControls/TBdi

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBdi`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBdi.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TBdi represents the HTML `<bdi>` element: text isolated from the direction of the text around it. It holds text of unknown direction, such as a user name inserted into a sentence, so right-to-left text does not reorder the punctuation and numbers beside it. Without `Dir`, it takes its direction from its own text.

The default tag is `bdi`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<li><com:TBdi><%# $this->Data['user'] %></com:TBdi>: 3 posts</li>
```

## Key Properties

| Property | Description |
|---|---|
| `Dir` | Inherited from TWebControl: `ltr`, `rtl`, `auto`, or empty (direction from the text) |

## Accessibility

- `<bdi>` has no ARIA role; screen readers read the text in its logical order, and the isolation changes only the display.
- Text in another language also sets `Lang`.

## Tests

- `tests/unit/Web/UI/WebControls/TBdiTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TBdo](./TBdo.md)

**@since 4.4.0**
