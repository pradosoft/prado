# Web/UI/WebControls/TBdo

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBdo`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBdo.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TBdo represents the HTML `<bdo>` element: text displayed in the direction given by `Dir`, overriding the direction of its characters. Text of unknown direction is a [TBdi](./TBdi.md).

The default tag is `bdo`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TBdo Dir="rtl">This text displays right to left.</com:TBdo>
```

## Key Properties

| Property | Description |
|---|---|
| `Dir` | `ltr` or `rtl`; default `ltr`, so `dir` always renders; `''` restores `ltr`; `auto` or another value throws `bdo_dir_invalid` |

## Accessibility

- `<bdo>` has no ARIA role; screen readers read the text in its logical order, so a TBdo changes only how it looks.

## Tests

- `tests/unit/Web/UI/WebControls/TBdoTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TBdi](./TBdi.md)

**@since 4.4.0**
