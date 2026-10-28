# Web/UI/WebControls/TCode

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCode`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCode.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TCode represents the HTML `<code>` element: a fragment of computer code, such as an element name, a file name, or a program statement. A block of code is a TCode inside a [TPre](./TPre.md).

The default tag is `code`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
Call <com:TCode>render()</com:TCode> after the page loads.
```

## Accessibility

- Browsers expose `<code>` with the `code` role.
- Screen readers read it as ordinary text.

## Tests

- `tests/unit/Web/UI/WebControls/TCodeTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TPre](./TPre.md)
- [TSamp](./TSamp.md)
- [TKbd](./TKbd.md)

**@since 4.4.0**
