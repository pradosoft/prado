# Web/UI/WebControls/TDialogClosedBy

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDialogClosedBy`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDialogClosedBy.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TEnumerable`

## Overview
The user actions that close a [TDialog](./TDialog.md), rendered as `closedby` in lower case.

| Value | `closedby` | The dialog closes on |
|---|---|---|
| `Auto` | not rendered | the browser default: Esc for a modal dialog, nothing for a non-modal one |
| `Any` | `any` | a click outside (light dismiss) or Esc |
| `CloseRequest` | `closerequest` | Esc |
| `None` | `none` | neither; only a button or script closes it |

**@since 4.4.0**
