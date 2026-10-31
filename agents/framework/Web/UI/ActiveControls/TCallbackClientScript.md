# Web/UI/ActiveControls/TCallbackClientScript

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TCallbackClientScript`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TCallbackClientScript.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Server-side API for client-side DOM updates during callback responses. Provides methods to show/hide elements, update content, set attributes, manage selections, trigger events, and apply visual effects. Accessed via `$page->getCallbackClient()`.

## Key Properties/Methods

- `update($element, $content)` - Set inner HTML through jQuery `.html()`; the content is parsed as HTML
- `setText($element, $text)` - Set the text content through jQuery `.text()`; the text is not parsed as HTML, so it takes user input (@since 4.4.0)
- `replaceContent($element, $content, $self = true)` - Replace the element or its content with HTML: a string, a rendered control, or a writer's content
- `appendContent()` / `prependContent()` / `insertContentAfter()` / `insertContentBefore()` - Insert an HTML fragment
- `setAttribute($control, $name, $value)` - Set HTML attribute
- `removeAttribute($control, $name)` - Remove an HTML attribute (@since 4.3.3)
- `setValue($input, $text)` - Set input element value
- `setListItems($control, $items)` - Update select dropdown options; a `TSuggestionList` delegates to `setSuggestionListItems()`
- `setSuggestionListItems($control, $items)` - Replace `<datalist>` options through `Prado.Element.setDataListOptions` (labels escape `<` and `>` and decode entities, as the page render does) (@since 4.4.0)
- `show($element)` / `hide($element)` / `toggle($element)` - Visibility
- `focus($element)` / `scrollTo($element, $options)` - Focus and scroll; `scrollTo` options are `duration` (ms, default 500; 0 or reduced motion jumps) and `offset` (px, default 50)
- `addCssClass($element, $cssClass)` / `removeCssClass($element, $cssClass)` - CSS classes
- `setStyle($element, $styles)` - Set CSS styles
- `select($control, $method, $value, $type)` - Select items in list controls
- `jQuery($element, $method, $params)` - Execute jQuery method
- `visualEffect($type, $element, $options)` - Apply visual effects
- `fadeIn($element)` / `fadeOut($element)` / `fadeTo($element, $value, $duration)` - Fade effects
- `slideDown($element)` / `slideUp($element)` - Slide effects

## HTML and Text

The content methods (`update()`, `replaceContent()`, `appendContent()`, `prependContent()`, `insertContentAfter()`, `insertContentBefore()`) insert HTML. `Prado.Element.replace()` and jQuery `.html()` run inline scripts in it, so content from user input is script execution on the client. Rules:

- Text from user input → `setText()`, or `THttpUtility::htmlEncode()` before a content method.
- An active control that pushes a text property with `update()` encodes it as its render does: `TActiveOutput` and `TActiveHyperLink` always, the `TEncodeTrait` controls when `Encode` is true.
- A rendered control or writer → `replaceContent()`; the control's own rendering decides the encoding.

## See Also

- [TActivePageAdapter](./TActivePageAdapter.md), [TCallbackClientSide](./TCallbackClientSide.md)
