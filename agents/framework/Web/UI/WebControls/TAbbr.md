# Web/UI/WebControls/TAbbr

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TAbbr`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TAbbr.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`TBaseTerm`](TBaseTerm.md)

## Overview
TAbbr renders the HTML5 `<abbr>` element: an abbreviation or acronym; `Title` is the expansion. It marks the other occurrences of its term and `Terms` in its target (the parent by default). Properties, matching, cost, and limits are in [TBaseTerm](TBaseTerm.md).

## Template Usage

```xml
<com:TAbbr Text="HTML" Title="HyperText Markup Language" />
```

| Property | Default |
|---|---|
| `TagName` | `abbr` |
| `MarkLimit` | `0` |

## Accessibility

Chromium exposes role `Abbr` with the `title` as its name; most screen readers do not announce it by default. Expand the abbreviation in the text at first use; `MarkLimit="1"` marks the first use only.

## See Also

- [TBaseTerm](TBaseTerm.md)

**@since 4.4.0**
