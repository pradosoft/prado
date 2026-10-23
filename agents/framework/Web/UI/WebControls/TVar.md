# Web/UI/WebControls/TVar

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TVar`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TVar.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`TBaseTerm`](TBaseTerm.md)

## Overview
TVar renders the HTML5 `<var>` element: a variable in math or programming, or a placeholder; rendered italic. It marks the other occurrences of its term and `Terms` in its target (the parent by default). Properties, matching, cost, and limits are in [TBaseTerm](TBaseTerm.md).

## Template Usage

```xml
<p>The area is <com:TVar Text="w" Terms="h" /> times h.</p>
```

| Property | Default |
|---|---|
| `TagName` | `var` |
| `MarkLimit` | `0` |

## Accessibility

No role (ignored, or `generic` with an `id`); screen readers read plain text. State the meaning of a variable in the text.

## See Also

- [TBaseTerm](TBaseTerm.md)

**@since 4.4.0**
