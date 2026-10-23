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

During a callback, `TActivePageAdapter::renderDeferredControl` renders each re-rendered active control (such as `TActivePanel`) with `render()` and runs that control's own `onRenderFilter` handlers on the output it writes into its `TCallbackResponseWriter`, keeping the boundary; exempt descendants are hidden from them. An ancestor with `RenderFilterFragments` runs its handlers next, innermost first, with `TRenderFilterParameter::getIsFragment()` true; other ancestors do not. The fragment is inside the start tags that enclose it in the ancestor's output, so skipped tags and `data-prado-filter-exempt` apply as in a full render:

| Context source | Start tag |
|---|---|
| Text of each control on the path, before the next path control | its open elements (comments, void, self-closed, and raw text elements open none) |
| `THtmlElement` on the path | its `TagName` |
| Other control with `data-prado-filter-exempt` in `Attributes` | `<div data-prado-filter-exempt="…">` |
| Element with an optional end tag (`p`, `li`, `dt`, `dd`, `option`, `optgroup`, `rb`, `rt`, `rtc`, `rp`) | left out, or a `div` with its exempt attribute; the fragment could close it |

`<!--prado-fragment-{nonce}-->` comments bound the fragment, which is cut out after the handlers; a handler that removes a marker leaves the fragment unfiltered. Other controls' own tags are not in the context. (@since 4.4.0)

[`TBaseTerm`](WebControls/TBaseTerm.md) (`TAbbr`, `TDfn`, `TVar`) attaches a handler to its target to mark terms.
