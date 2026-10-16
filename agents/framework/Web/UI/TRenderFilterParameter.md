# Web/UI/TRenderFilterParameter

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TRenderFilterParameter`**

## Class Info
**Location:** `framework/Web/UI/TRenderFilterParameter.php`
**Namespace:** `Prado\Web\UI`
**Extends:** `TEventParameter`
**Implements:** `IEventCycleParameter`
**Since:** 4.3.3

## Overview

Event parameter for the `onRenderFilter` event raised by `TControl::renderControl`. Carries the rendered HTML and exposes two transparently-switchable representations — an HTML string and a `DOMDocument` — plus the libxml parse error list. All three are stored in the parent `TEventParameter` array under reserved keys.

## Constants

| Constant | Value | Description |
|---|---|---|
| `RENDER_FILTER_TEXT` | `'html'` | Array key for the HTML string |
| `RENDER_FILTER_DOM` | `'dom'` | Array key for the DOMDocument |
| `RENDER_FILTER_ERRORS` | `'errors'` | Array key for the libxml error list |

## Resource switching

The parameter tracks which representation is *current*:
- `getFilterDOM()` (or `$param['dom']`) → parses HTML into DOM, makes DOM current.
- `getFilterText()` (or `$param['html']`) while DOM is current → serialises DOM back to HTML, makes string current.
- `setFilterText()` / `setFilterDOM()` makes the set representation current and discards the other.
- `postRaiseEvent` automatically serialises DOM → HTML after all handlers run, so `processRenderFilter` always receives a valid string.

## Parsing

`htmlToDom()` parses in the context of the element holding the first tag (`getFragmentContext()`):

| First tag | Context |
|---|---|
| `<!DOCTYPE>`, `<html>` | `#document` |
| `<head>`, `<body>` | `html` |
| `<tr>` | `tbody` |
| `<td>`, `<th>` | `tr` |
| `<caption>`, `<colgroup>`, `<thead>`, `<tbody>`, `<tfoot>` | `table` |
| `<col>` | `colgroup` |
| `<option>`, `<optgroup>` | `select` |
| other, or text | `body` |

| PHP (`getUseHtml5Parser()`) | Parser |
|---|---|
| 8.4+ | `Dom\HTMLDocument` (HTML5); nodes import into the legacy `DOMDocument`. `<template>` content re-parses from its `innerHTML` (the HTML5 DOM keeps it outside the child nodes). `<html>`/`<head>`/`<body>` without a start tag in the source are left out. Non-XML names (`@click`, `[prop]`) import under a placeholder name. |
| 8.2, 8.3 | libxml `loadHTML()` inside a wrapper of the context element, then unwrapped. `<script>`/`<style>` content and `<svg>`/`<math>` elements become placeholder comments (`protectRawMarkup()`); handlers do not see or change them. libxml drops non-XML attribute names. |

`domToHtml()` serializes with `saveHTML()`, writes the doctype itself, and restores placeholders. Placeholders carry a per-parse random nonce.

Before 4.4.0 the parse used `LIBXML_HTML_NOIMPLIED` with no context: leading text gained a `<p>`, whitespace between top-level elements was dropped, `</` ended script content, SVG names were lowercased, and the `<head>` tag and doctype were lost.

## Key Methods

### HTML accessor

| Method | Description |
|---|---|
| `getFilterText(): string` | Current HTML (serialises from DOM first if DOM is current) |
| `setFilterText(string $html): void` | Replace HTML; discard DOM and errors |

### DOM accessor

| Method | Description |
|---|---|
| `getFilterDOM(): DOMDocument\|false` | Parsed DOM (lazy parse on first call); `false` on fatal libxml failure |
| `setFilterDOM(DOMDocument $dom): void` | Replace DOM; clear errors |

### Error accessors

| Method | Description |
|---|---|
| `getFilterErrors(): ?array` | `LibXMLError[]` from the most recent parse, or `null` when no errors |
| `getHasFilterError(): bool` | `true` when at least one libxml error was captured |

### DOM walker

```php
$param->walkElements(function (\DOMElement $el, $param, int $depth) {
    if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
        $el->setAttribute('alt', '');
    }
});
```

`walkElements(callable, ?DOMNode $node = null, bool $recursive = true)` — depth-first traversal of every `DOMElement`. The visit list is snapshotted before the first callback, so DOM mutations during the walk do not affect which elements are visited.

## Array-access

All three reserved keys are proxied through the getters/setters:

```php
$param[TRenderFilterParameter::RENDER_FILTER_TEXT]           // → getFilterText()
$param[TRenderFilterParameter::RENDER_FILTER_TEXT] = $html;  // → setFilterText()
$param[TRenderFilterParameter::RENDER_FILTER_DOM]            // → getFilterDOM()
$param[TRenderFilterParameter::RENDER_FILTER_DOM] = $dom;    // → setFilterDOM() (must be DOMDocument)
$param[TRenderFilterParameter::RENDER_FILTER_ERRORS]         // → getFilterErrors() (null or array)
$param[TRenderFilterParameter::RENDER_FILTER_ERRORS] = $v;   // no-op — errors are read-only
unset($param[TRenderFilterParameter::RENDER_FILTER_ERRORS]); // clears errors → null
```

Extra keys (not one of the three reserved ones) pass through to the parent `TEventParameter` array and can be used for handler-to-handler state passing.

## Error semantics

`RENDER_FILTER_ERRORS` stores `null` when no errors have been captured (fresh instance, clean parse, or after `setFilterText`/`setFilterDOM`). A non-null value means the last parse produced at least one libxml error. Use `getHasFilterError()` as the canonical check.

## Subclassing

Override `htmlToDom(string $html): DOMDocument|false` to substitute a custom parser. Call `$this->storeErrors($errors)` inside the override to keep the errors slot consistent (it bypasses the public no-op on `offsetSet('errors', ...)`).

## Typical handler

```php
$control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
    // String API
    $param->setFilterText(strtoupper($param->getFilterText()));

    // DOM API
    $dom = $param->getFilterDOM(); // DOMDocument|false
    if ($dom !== false) {
        $param->walkElements(function (\DOMElement $el, $p) {
            if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
                $el->setAttribute('alt', '');
            }
        });
    }

    // Extra state for a downstream handler
    $param['processed-by'] = 'my-filter';
};
```
