# Web/UI/IFilterRenderable

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`IFilterRenderable`**

## Interface Info
**Location:** `framework/Web/UI/IFilterRenderable.php`
**Namespace:** `Prado\Web\UI`
**Extends:** `IRenderable`
**Since:** 4.3.3

## Overview

Marks a control as supporting render-output filtering via the `onRenderFilter` event. `TControl::renderControl` and `TControl::renderChildren` detect this interface and automatically wrap the render call in a capture-and-restore filter lifecycle (`preRenderFilter` / `processRenderFilter`). Implement using [`TFilterRenderableTrait`](Traits/TFilterRenderableTrait.md).

## Methods

| Method | Description |
|---|---|
| `hasEventHandler($name)` | Returns whether at least one handler is registered for the named event. Required so `TControl::preRenderFilter` can test the event without assuming a `TComponent` base. |
| `onRenderFilter($output)` | Raises the `onRenderFilter` event, passes HTML through handlers via a [`TRenderFilterParameter`](TRenderFilterParameter.md), returns the (possibly modified) HTML string. |

## Implementation

Implement the interface by using [`TFilterRenderableTrait`](Traits/TFilterRenderableTrait.md), which provides `onRenderFilter`. `hasEventHandler` is satisfied by `TComponent`, which all practical implementors extend:

```php
class MyControl extends TComponent implements IFilterRenderable
{
    use TFilterRenderableTrait;

    public function render($writer): void
    {
        $writer->write('<p>content</p>');
    }
}
```

`TControl` already implements `IFilterRenderable` — no extra work is needed for controls that extend it.

## Filter lifecycle

When `TControl::renderChildren` encounters a non-`TControl` child that implements `IFilterRenderable`:
1. `preRenderFilter($writer, $child)` — checks `$child->hasEventHandler('onRenderFilter')`. If true, swaps the writer's inner buffer and saves the original.
2. `$child->render($writer)` — renders into the capture buffer.
3. `processRenderFilter($writer, $oldWriter, $child)` — flushes the buffer, calls `$child->onRenderFilter($output)`, writes the result to the original writer, and restores it.

If no handler is registered, all three steps are no-ops and output goes directly to the writer.
