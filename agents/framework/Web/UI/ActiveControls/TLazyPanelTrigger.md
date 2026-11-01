# Web/UI/ActiveControls/TLazyPanelTrigger

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TLazyPanelTrigger`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TLazyPanelTrigger.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Extends:** `TEnumerable`

## Overview
When a [TLazyPanel](./TLazyPanel.md) requests its content.

| Value | Request |
|---|---|
| `Load` | When the page loads |
| `Visible` | When the panel enters the viewport extended by `RootMargin`; at page load without `IntersectionObserver`. The default |
| `Manual` | When the client calls `Prado.WebUI.TLazyPanel.load(id)` |

**@since 4.4.0**
