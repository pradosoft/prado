# Web/UI/WebControls/TKbd

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TKbd`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TKbd.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TKbd represents the HTML `<kbd>` element: user input such as a key, a key combination, or a spoken command. A key combination nests one TKbd per key inside an outer TKbd.

The default tag is `kbd`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TKbd><com:TKbd>Ctrl</com:TKbd>+<com:TKbd>C</com:TKbd></com:TKbd>
```

## Accessibility

- `<kbd>` has no ARIA role; screen readers read the key names as text.
- A symbol such as ⌘ or ⇧ is read inconsistently; write the key name, such as `Command`.

## Tests

- `tests/unit/Web/UI/WebControls/TKbdTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TCode](./TCode.md)
- [TSamp](./TSamp.md)

**@since 4.4.0**
