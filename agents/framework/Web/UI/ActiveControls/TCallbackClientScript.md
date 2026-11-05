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
- `replaceContent($element, $content, $self = true, $morph = false)` - Replace the element or its content with HTML: a string, a rendered control, or a writer's content. `$morph` patches the existing nodes through `Prado.Element.morph` (idiomorph), which keep their listeners, focus, and caret; the client function then gets a fifth argument, `true` (4.4.0). See [TActiveUpdateMode](./TActiveUpdateMode.md)
- `getViewTransition()` / `setViewTransition($value)` - Whether the client applies the functions of this response inside a view transition; defaults to false. See [CallbackViewTransition](./CallbackViewTransition.md) (4.4.0)
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

## Browser Methods (4.4.0)

These reach outside the page through `Prado.Browser` and `Prado.Notification` in `controls/browser.js` (the `prado` package, so always loaded). Each does nothing in a browser without the API.

- `notify($title, $options = [], $clickTarget = null, $clickParameter = null)` - `Prado.Notification.show`: a system notification. `$options` are the Notification constructor options (`body`, `icon`, `tag`, `lang`, `dir`, `badge`, `image`, `silent`, `requireInteraction`, `renotify`, `data`) plus `focus` (click focuses the window, default true) and `closeAfter` (ms). `$clickTarget` (a control or unique ID) becomes `EventTarget` and `$clickParameter` becomes `CallbackParameter`: a click runs `Prado.Callback(EventTarget, CallbackParameter)`, so the control's `raiseCallbackEvent()` gets the parameter. The click also dispatches `prado:notificationclick` on the document.
- `setAppBadge($count = null)` / `clearAppBadge()` - `Prado.Browser.setAppBadge` / `clearAppBadge` (Badging API; installed web apps only). Null shows a flag, 0 clears, negative throws `InvalidArgumentException`.
- `setDocumentTitle($title)` - `Prado.Browser.setTitle`
- `vibrate($pattern)` - `Prado.Browser.vibrate`; an int or an int array; 0 cancels.

Permission: the browser grants `Notification.requestPermission()` only from a user interaction, and a callback response arrives after one at best within the activation window. A page asks from the client, such as a button's `OnClientClick="Prado.Notification.requestPermission()"`, and reads the state with `Prado.Notification.getPermission()` or `Prado.Browser.queryPermission('notifications')` (`granted`, `denied`, `prompt`, `unsupported`). `notify()` with permission `default` asks first; with `denied` it shows nothing. A notification shows while the tab is open, in the foreground or background; a closed tab needs Web Push, which is outside the framework.

## HTML and Text

The content methods (`update()`, `replaceContent()`, `appendContent()`, `prependContent()`, `insertContentAfter()`, `insertContentBefore()`) insert HTML. `Prado.Element.replace()` and jQuery `.html()` run inline scripts in it, so content from user input is script execution on the client. Rules:

- Text from user input → `setText()`, or `THttpUtility::htmlEncode()` before a content method.
- An active control that pushes a text property with `update()` encodes it as its render does: `TActiveOutput` and `TActiveHyperLink` always, the `TEncodeTrait` controls when `Encode` is true.
- A rendered control or writer → `replaceContent()`; the control's own rendering decides the encoding.

## See Also

- [TActivePageAdapter](./TActivePageAdapter.md), [TCallbackClientSide](./TCallbackClientSide.md)
