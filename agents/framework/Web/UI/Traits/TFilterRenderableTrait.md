# Web/UI/Traits/TFilterRenderableTrait

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [Traits](./INDEX.md) / **`TFilterRenderableTrait`**

## Class Info
**Location:** `framework/Web/UI/Traits/TFilterRenderableTrait.php`
**Namespace:** `Prado\Web\UI\Traits`

## Overview
Provides the `onRenderFilter(string $renderedText): string` method required by [IFilterRenderable](../IFilterRenderable.md). Use this trait in any `TComponent` subclass that implements `IFilterRenderable`.

The capture-and-restore lifecycle (`preRenderFilter`, `processRenderFilter`, `newRenderFilterWriter`) lives in `TControl` and calls `onRenderFilter` at the right moment during `TControl::renderControl`. The trait only supplies the event-raising method itself.

## Provided Method

```php
public function onRenderFilter(string $renderedText): string
```

Creates a [TRenderFilterParameter](../TRenderFilterParameter.md) from `$renderedText`, raises the `onRenderFilter` event so handlers can modify the HTML or DOM, then returns the filtered string. After all handlers run, `TRenderFilterParameter::postRaiseEvent` automatically serialises any current DOM representation back to an HTML string — handlers that work exclusively through the DOM API need not call `getFilterText()` themselves.

## Usage

```php
use Prado\Web\UI\IFilterRenderable;
use Prado\Web\UI\Traits\TFilterRenderableTrait;

class MyControl extends TCompositeControl implements IFilterRenderable
{
    use TFilterRenderableTrait;
    // hasEventHandler() is inherited from TComponent via TControl
}

// Attach a string-based filter:
$control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
    $param->setFilterText(strtoupper($param->getFilterText()));
};

// Attach a DOM-based filter — add missing alt attributes to every <img>:
$control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
    $dom = $param->getFilterDOM(); // DOMDocument|false
    if ($dom === false) {
        return;
    }
    $param->walkElements(function (\DOMElement $el, TRenderFilterParameter $p) {
        if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
            $el->setAttribute('alt', '');
        }
    });
    // DOM → HTML serialisation is automatic
};
```

## See Also

- [IFilterRenderable](../IFilterRenderable.md)
- [TRenderFilterParameter](../TRenderFilterParameter.md)
- [TControl](../TControl.md)

**@since 4.3.3**
