# Web/UI/WebControls/TDfn

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDfn`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDfn.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`TBaseTerm`](TBaseTerm.md)

## Overview
TDfn renders the HTML5 `<dfn>` element: the defining instance of a term; a `Title` holds only the defined term. It marks the first occurrence of each of its `Terms` in its target (the parent by default). Its own element is the defining instance of its term, so a TDfn inside its target marks nothing else for it. Properties, matching, cost, and limits are in [TBaseTerm](TBaseTerm.md).

## Template Usage

```xml
<p>A <com:TDfn ID="RenderFilter" Text="render filter" /> changes the output.</p>
```

| Property | Default |
|---|---|
| `TagName` | `dfn` |
| `MarkLimit` | `1` |

## Accessibility

Role `term`, named from its content. Set an `ID` so other text can link to the definition.

## See Also

- [TBaseTerm](TBaseTerm.md)

**@since 4.4.0**
