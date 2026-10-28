# Web/UI/WebControls/TSub

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSub`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSub.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TSub represents the HTML `<sub>` element: a subscript required by a typographical convention, such as the 2 in H₂O. It is not for styling alone.

The default tag is `sub`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
H<com:TSub>2</com:TSub>O
```

## Accessibility

- Browsers expose `<sub>` with the `subscript` role.
- Most screen readers do not announce a subscript; text that depends on it says so in words or in a visually hidden span.

## Tests

- `tests/unit/Web/UI/WebControls/TSubTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TSup](./TSup.md)

**@since 4.4.0**
