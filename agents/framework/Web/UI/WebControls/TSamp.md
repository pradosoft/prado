# Web/UI/WebControls/TSamp

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSamp`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSamp.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TSamp represents the HTML `<samp>` element: sample or quoted output from a program or computing system.

The default tag is `samp`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
The command prints <com:TSamp>File not found.</com:TSamp>
```

## Accessibility

- `<samp>` has no ARIA role; screen readers read it as ordinary text.

## Tests

- `tests/unit/Web/UI/WebControls/TSampTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TCode](./TCode.md)
- [TKbd](./TKbd.md)

**@since 4.4.0**
