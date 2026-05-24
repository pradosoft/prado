# Web/UI/IFilterRenderable

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`IFilterRenderable`**

## Class Info
**Location:** `framework/Web/UI/IFilterRenderable.php`
**Namespace:** `Prado\Web\UI`

## Overview
Interface that marks a control as supporting render-output filtering via the `onRenderFilter` event. Extends `IRenderable`.

`TControl::renderControl` and `TControl::renderChildren` detect this interface and automatically handle the capture-and-restore lifecycle: when at least one `onRenderFilter` handler is registered, the writer's inner `ITextWriter` is swapped for a fresh buffer, `render()` runs into that buffer, and the captured HTML is then passed through `onRenderFilter` handlers before being written to the real output.

Implement using [TFilterRenderableTrait](./Traits/TFilterRenderableTrait.md).

## Interface Methods

| Method | Description |
|---|---|
| `hasEventHandler(string $name): bool` | Returns whether at least one handler is registered for the named event. Required so `TControl::preRenderFilter` can test `onRenderFilter` without assuming the object is a `TComponent`. |
| `onRenderFilter(string $renderedText): string` | Raises the `onRenderFilter` event via a [TRenderFilterParameter](./TRenderFilterParameter.md), passing captured HTML through all registered handlers, and returns the (possibly modified) string. |

Inherits `render(THtmlWriter $writer)` from `IRenderable`.

## How to Implement

```php
use Prado\Web\UI\IFilterRenderable;
use Prado\Web\UI\Traits\TFilterRenderableTrait;

class MyControl extends TCompositeControl implements IFilterRenderable
{
    use TFilterRenderableTrait;
    // onRenderFilter() and hasEventHandler() are provided by the trait + TComponent
}
```

## Patterns & Gotchas

- **No-op when no handlers** — `preRenderFilter` calls `hasEventHandler('onRenderFilter')` and returns `null` (no buffer swap) when the result is false. Zero overhead for controls without handlers.
- **`TControl` itself implements this** — `TControl` implements `IFilterRenderable` via `TFilterRenderableTrait`, so all controls can receive `onRenderFilter` handlers out of the box.
- **Non-`TControl` implementors** — `renderChildren` also checks `IFilterRenderable` on non-`TControl` children (plain `IRenderable` objects), applying the same lifecycle when they implement this interface.

## See Also

- [TFilterRenderableTrait](./Traits/TFilterRenderableTrait.md)
- [TRenderFilterParameter](./TRenderFilterParameter.md)
- [TControl](./TControl.md)

**@since 4.3.3**
