# Web/UI/TRenderFilterParameter

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TRenderFilterParameter`**

## Class Info
**Location:** `framework/Web/UI/TRenderFilterParameter.php`
**Namespace:** `Prado\Web\UI`
**Extends:** `TEventParameter`
**Implements:** `IEventCycleParameter`

## Overview
Event parameter for the `onRenderFilter` event raised by `TControl::renderControl`. It carries the captured rendered HTML and exposes two representations that can be switched between transparently:

- **HTML string** — raw rendered markup, via `getFilterText()` / `setFilterText()` or array-access key `'html'` (`RENDER_FILTER_TEXT`).
- **DOMDocument** — a parsed DOM tree, via `getFilterDOM()` / `setFilterDOM()` or array-access key `'dom'` (`RENDER_FILTER_DOM`).

The parameter tracks which representation is *current* (authoritative) and lazily syncs between them.

## Constants

| Constant | Value | Description |
|---|---|---|
| `RENDER_FILTER_TEXT` | `'html'` | Array-access key for the HTML string |
| `RENDER_FILTER_DOM` | `'dom'` | Array-access key for the DOMDocument |
| `RENDER_FILTER_ERRORS` | `'errors'` | Array-access key for libxml parse errors (`LibXMLError[]` or `null`) |

## Resource Switching

| Action | Effect |
|---|---|
| `getFilterDOM()` | Parses HTML → DOM (if needed); makes DOM the current resource |
| `getFilterText()` | Serialises DOM → HTML (if DOM is current); makes string the current resource |
| `setFilterText($html)` | Sets string; discards DOM cache and parse errors; string becomes current |
| `setFilterDOM($dom)` | Sets DOM; DOM becomes current |

## Key Methods

- `getFilterText(): string` — Current HTML string (syncs from DOM first if needed).
- `setFilterText(string $html): void` — Replaces the HTML string; discards any cached DOM.
- `getFilterDOM(): DOMDocument|false` — Lazily parsed DOM; `false` on fatal libxml parse failure. Makes DOM current.
- `setFilterDOM(DOMDocument $dom): void` — Replaces the DOM; makes DOM current.
- `getFilterErrors(): ?array` — `LibXMLError[]` from the last parse, or `null` when no errors occurred.
- `getHasFilterError(): bool` — `true` when the last parse captured at least one libxml error.
- `walkElements(callable $callback, ?DOMNode $node = null, bool $recursive = true): void` — Depth-first traversal of every `DOMElement` in the document (or a subtree). Callback signature: `(DOMElement $el, TRenderFilterParameter $p, int $depth): void`. The visit list is snapshotted before the first callback fires, so DOM mutations during the walk do not affect which elements are visited.
- `postRaiseEvent(...)` — `IEventCycleParameter` hook. Serialises DOM → HTML after all handlers run so `TControl::processRenderFilter` always receives a valid string.

## Array-Access Behaviour

```php
$param[TRenderFilterParameter::RENDER_FILTER_TEXT]      // proxies getFilterText()
$param[TRenderFilterParameter::RENDER_FILTER_DOM]       // proxies getFilterDOM()
$param[TRenderFilterParameter::RENDER_FILTER_ERRORS]    // proxies getFilterErrors()

$param['html'] = '<p>new</p>';     // setFilterText()
$param['dom']  = $domDocument;     // setFilterDOM()
unset($param['html']);             // clears string to ''
unset($param['dom']);              // commits DOM→HTML then discards DOM
unset($param['errors']);           // clears stored parse errors
```

## Usage Examples

```php
// String-based handler
$control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
    $param->setFilterText(strtoupper($param->getFilterText()));
};

// DOM-based handler — add missing alt attributes to all <img> elements
$control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
    $dom = $param->getFilterDOM(); // DOMDocument|false
    if ($dom === false) {
        return; // libxml could not parse the fragment
    }
    $param->walkElements(function (\DOMElement $el, TRenderFilterParameter $p) {
        if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
            $el->setAttribute('alt', '');
        }
    });
    // DOM → HTML serialisation is automatic via postRaiseEvent
};
```

## Patterns & Gotchas

- **DOM parsed with `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD`** — no `<html>/<head>/<body>` wrappers are added. The processing instruction `<?xml encoding="UTF-8">` is injected for correct encoding and then removed after parsing.
- **`false` return from `getFilterDOM()`** — means libxml reported a fatal parse failure. The HTML string remains current and unmodified. Check `getFilterErrors()` for details.
- **Parse errors are always retained** — even when parsing succeeded, libxml warnings/notices are captured. Only `null` means "no errors at all".
- **`postRaiseEvent` serialises automatically** — handlers that work exclusively through the DOM API do not need to call `getFilterText()` themselves.

## See Also

- [IFilterRenderable](./IFilterRenderable.md)
- [TFilterRenderableTrait](./Traits/TFilterRenderableTrait.md)
- [TControl](./TControl.md)

**@since 4.3.3**
