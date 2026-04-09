# TView

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TView](./TView.md)

**Location:** `framework/Web/UI/WebControls/TView.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TView is a container control used exclusively inside a `TMultiView`. It holds a group of child controls and is shown or hidden by toggling the `Active` property. Only one `TView` within a `TMultiView` should be active at a time (enforced by `TMultiView`, not by `TView` itself).

TView extends `[TControl](./TControl.md)` directly (not `TWebControl`) — it renders no wrapping HTML element of its own; its children are rendered inline.

## Inheritance

`TView` → `TControl` → `TComponent`

## Key Constants / Enums

None.

## Key Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `Active` | bool | `false` | Whether this view is the currently displayed view. Setting `Active` also calls `parent::setVisible($value)` on the base control. |
| `Visible` | bool | — | **Read-only proxy.** Returns `true` only when `Active=true` AND the parent is visible. Setting `Visible` throws `TInvalidOperationException`. Use `Active` instead. |

## Key Methods

| Method | Description |
|---|---|
| `getActive()` / `setActive($value)` | Get/set the active state. `setActive` calls `parent::setVisible($value)` to synchronise TControl's own visibility flag. |
| `getVisible($checkParents = true)` | Returns `true` when `Active=true` and (if `$checkParents=true`) the parent is also visible. Returns `false` if the parent is null and `Active=false`. |
| `setVisible($value)` | Throws `TInvalidOperationException` — use `Active` instead. |
| `onActivate($param)` | Raises the `OnActivate` event. |
| `onDeactivate($param)` | Raises the `OnDeactivate` event. |

## Events

| Event | Raised When |
|---|---|
| `OnActivate` | The view is activated (raised by `TMultiView` when it sets `Active=true`) |
| `OnDeactivate` | The view is deactivated (raised by `TMultiView` when it sets `Active=false`) |

## Patterns & Gotchas

- **Always used inside `TMultiView`** — TView has no meaningful standalone behaviour. `TMultiView` manages which view is active and raises `OnActivate`/`OnDeactivate` when switching.
- **`Visible` is read-only** — Do not attempt to set `Visible` directly; it throws an exception. Set `Active` instead, or let `TMultiView::setActiveViewIndex()` / `TMultiView::setActiveView()` manage it.
- **No wrapping element** — TView renders its child controls directly without a surrounding `<div>` or other tag. To add a container, place a `TPanel` as a child.
- **Visibility check includes parent** — `getVisible(true)` checks the parent `TMultiView`'s visibility in addition to `Active`. If the parent multi-view itself is hidden, no view appears visible.
- **Active vs Visible** — `Active` is the authoritative state flag. `getVisible()` derives from `Active` plus parent state; it is not independently settable.
