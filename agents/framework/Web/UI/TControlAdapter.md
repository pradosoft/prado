# TControlAdapter

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TControlAdapter](./TControlAdapter.md)

**Location:** `framework/Web/UI/TControlAdapter.php`
**Namespace:** `Prado\Web\UI`

## Overview

Base class for the Adapter pattern applied to [TControl](./TControl.md). An adapter is attached to a control to override or extend specific lifecycle or rendering behaviors without subclassing the control itself.

Extends [TApplicationComponent](../TApplicationComponent.md). Holds a reference to the control it is adapting (`$_control`) and provides default pass-through implementations of every lifecycle and render method — each simply delegates to the corresponding method on the attached control. Subclasses override only the methods they need to customize.

Access the page via `getPage()` which delegates to `$_control->getPage()` (returns `null` if no control is set).

## Key Properties

| Property | Type | Description |
|---|---|---|
| `$_control` | [TControl](./TControl.md) | The control this adapter is attached to (set in constructor, protected) |

## Key Methods

### Construction

- `__construct(TControl $control)` — Stores the control reference; calls `parent::__construct()`.
- `getControl()` — Returns the attached [TControl](./TControl.md).
- `getPage()` — Returns `$_control->getPage()` (or `null` if no control).

### Lifecycle Delegates (all delegate to `$_control` by default)

| Method | Delegated to | Stage |
|---|---|---|
| `createChildControls()` | `$_control->createChildControls()` | Before init |
| `loadState()` | `$_control->loadState()` | State loading |
| `saveState()` | `$_control->saveState()` | State saving |
| `onInit($param)` | `$_control->onInit($param)` | Init stage |
| `onLoad($param)` | `$_control->onLoad($param)` | Load stage |
| `onPreRender($param)` | `$_control->onPreRender($param)` | Pre-render stage |
| `onUnload($param)` | `$_control->onUnload($param)` | Unload stage |

### Render Delegates

| Method | Delegated to |
|---|---|
| `render(THtmlWriter $writer)` | `$_control->render($writer)` |
| `renderChildren(THtmlWriter $writer)` | `$_control->renderChildren($writer)` |

## Patterns & Gotchas

- **Pure delegation by default** — every method in the base class calls through to the control. Subclasses only override what they need to change; the rest falls through unchanged.
- **Attach via `TControl::setAdapter()`** — the framework calls adapter methods in place of the control's own methods during the page lifecycle. The control must be configured to use the adapter.
- **`TApplicationComponent` inheritance** — the adapter has full access to `getApplication()`, `getService()`, `getRequest()`, `getResponse()`, etc., making it suitable for complex cross-cutting concerns.
- **Not a `TModule`** — adapters are not registered in application configuration; they are created programmatically and attached to a control instance.
- **Used in ActiveControls** — the active control system (`ActiveControls/`) uses adapters to intercept rendering and lifecycle for AJAX callback responses without changing the base control classes.
- **`$_control` is `protected`** — subclasses can access it directly rather than through `getControl()` when needed for efficiency in tight rendering loops.

(End of file - total 54 lines)
