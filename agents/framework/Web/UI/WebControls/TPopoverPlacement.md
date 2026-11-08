# Web/UI/WebControls/TPopoverPlacement

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPopoverPlacement`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPopoverPlacement.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TEnumerable`

## Overview
The side of its anchor where a [TPopover](./TPopover.md) shows, rendered as `data-placement` in lower case. The stylesheet maps each to a logical `position-area` and flips it when it does not fit.

| Value | `data-placement` | `position-area` | The popover shows |
|---|---|---|---|
| `None` | not rendered | none | at the browser default position, centered in the viewport |
| `Top` | `top` | `block-start` | above the anchor |
| `Bottom` | `bottom` | `block-end` | below the anchor |
| `Start` | `start` | `inline-start` | at the inline start; left in a left-to-right page |
| `End` | `end` | `inline-end` | at the inline end; right in a left-to-right page |

**@since 4.4.0**
