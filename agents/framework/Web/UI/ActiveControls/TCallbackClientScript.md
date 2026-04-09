# TCallbackClientScript

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TCallbackClientScript](./TCallbackClientScript.md)

**Location:** `framework/Web/UI/ActiveControls/TCallbackClientScript.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Server-side API for client-side DOM updates during callback responses. Provides methods to show/hide elements, update content, set attributes, manage selections, trigger events, and apply visual effects. Accessed via `$page->getCallbackClient()`.

## Key Properties/Methods

- `update($element, $content)` - Replace innerHTML
- `replaceContent($element, $content)` - Replace element content
- `setAttribute($control, $name, $value)` - Set HTML attribute
- `setValue($input, $text)` - Set input element value
- `setListItems($control, $items)` - Update select dropdown options
- `show($element)` / `hide($element)` / `toggle($element)` - Visibility
- `focus($element)` / `scrollTo($element)` - Focus and scroll
- `addCssClass($element, $cssClass)` / `removeCssClass($element, $cssClass)` - CSS classes
- `setStyle($element, $styles)` - Set CSS styles
- `select($control, $method, $value, $type)` - Select items in list controls
- `jQuery($element, $method, $params)` - Execute jQuery method
- `visualEffect($type, $element, $options)` - Apply visual effects
- `fadeIn($element)` / `fadeOut($element)` / `fadeTo($element, $value, $duration)` - Fade effects
- `slideDown($element)` / `slideUp($element)` - Slide effects

## See Also

- [TActivePageAdapter](./TActivePageAdapter.md), [TCallbackClientSide](./TCallbackClientSide.md)
