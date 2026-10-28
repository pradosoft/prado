# Web/UI/WebControls/TDataValue

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataValue`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataValue.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TDataValue represents the HTML `<data>` element: content paired with a machine-readable `Value`, such as a product code for a product name. A date or time is a [TTime](./TTime.md). The class is not named TData so the short name stays clear of user classes and the `Prado\Data` layer.

The default tag is `data`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TDataValue Value="398">Mini Ketchup</com:TDataValue>
```

## Key Properties

| Property | Description |
|---|---|
| `Value` | Machine-readable value; `value` always renders, as `<data>` requires it |

## Accessibility

- `<data>` has no ARIA role; screen readers read the content and not the value, so the content is the human-readable form.

## Tests

- `tests/unit/Web/UI/WebControls/TDataValueTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TTime](./TTime.md)

**@since 4.4.0**
