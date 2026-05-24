# Web/UI/IAdapterControl

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`IAdapterControl`**

## Class Info
**Location:** `framework/Web/UI/IAdapterControl.php`
**Namespace:** `Prado\Web\UI`

## Overview
Interface defining the contract for objects returned by `TControl::getAdapterControl()`. That method returns either the control itself or its attached `TControlAdapter`. Both `TControl` and `TControlAdapter` implement this interface so the framework can dispatch lifecycle calls through a single, uniform pointer without knowing whether an adapter is present.

All lifecycle methods, the render entry-point, and state hooks are called through this interface during the page request cycle.

## Interface Methods

| Method | Description |
|---|---|
| `createChildControls()` | Creates child controls |
| `onInit($param)` | Called at the `OnInit` lifecycle stage |
| `onLoad($param)` | Called at the `OnLoad` lifecycle stage |
| `onPreRender($param)` | Called at the `OnPreRender` lifecycle stage |
| `onUnload($param)` | Called at the `OnUnload` lifecycle stage |
| `render(THtmlWriter $writer)` | Renders the control |
| `loadState()` | Loads additional persistent control state |
| `saveState()` | Saves additional persistent control state |

## Implementors

- **`TControl`** — implements it directly; `getAdapterControl()` returns `$this` when no adapter is set.
- **`TControlAdapter`** — base adapter class; each method delegates to the attached control by default. Subclasses override only what they need.

## Patterns & Gotchas

- **Purpose of `getAdapterControl()`** — `TControl::initRecursive`, `loadRecursive`, `preRenderRecursive`, `unloadRecursive`, `renderControl`, and the state hooks all route through `getAdapterControl()` so that an adapter transparently intercepts any or all of these stages.
- **Attach via `TControl::setAdapter()`** — once an adapter is attached, `getAdapterControl()` returns it instead of the control itself.
- **Adding adapters post-init** — setting an adapter after the control has been initialized can result in lifecycle methods being called on the adapter for stages the control has already passed.

## See Also

- [TControlAdapter](./TControlAdapter.md)
- [TControl](./TControl.md)

**@since 4.3.3**
