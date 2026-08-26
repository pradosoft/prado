# Web/UI/IAdapterControl

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`IAdapterControl`**

## Interface Info
**Location:** `framework/Web/UI/IAdapterControl.php`
**Namespace:** `Prado\Web\UI`
**Since:** 4.3.3

## Overview

Common contract for the object returned by `TControl::getAdapterControl()`. That method returns either the control itself or its `TControlAdapter` when one is set, so both classes implement this interface. Lifecycle methods, the render entry-point, and state hooks are all called through it, letting `TControl` dispatch to either the adapter or itself with a single typed call.

## Methods

| Method | Description |
|---|---|
| `createChildControls()` | Creates child controls |
| `onInit($param)` | Invoked at the `OnInit` lifecycle stage |
| `onLoad($param)` | Invoked at the `OnLoad` lifecycle stage |
| `onPreRender($param)` | Invoked at the `OnPreRender` lifecycle stage |
| `onUnload($param)` | Invoked at the `OnUnload` lifecycle stage |
| `render($writer)` | Renders the control to `$writer` |
| `loadState()` | Loads additional persistent control state |
| `saveState()` | Saves additional persistent control state |

## Implementors

- **[`TControl`](TControl.md)** — satisfies all methods natively; `getAdapterControl()` returns `$this` when no adapter is set.
- **[`TControlAdapter`](TControlAdapter.md)** — provides pass-through implementations that delegate to the attached control; subclasses override only what they customise.

## Usage

```php
// Inside TControl — getAdapterControl() is protected; returns IAdapterControl
$this->getAdapterControl()->onPreRender($param);
$this->getAdapterControl()->render($writer);
```

Application code never calls `getAdapterControl()` directly; it is used exclusively inside `TControl`'s own lifecycle and rendering methods.
