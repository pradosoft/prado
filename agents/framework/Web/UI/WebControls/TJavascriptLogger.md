# Web/UI/WebControls/TJavascriptLogger

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TJavascriptLogger`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TJavascriptLogger.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
Embeds a client-side JavaScript logging console into the page. The console can be toggled visible/hidden with a keyboard shortcut (ALT + configured key). Useful for debugging client-side behavior in production without requiring browser devtools.

Extends `[TWebControl](./TWebControl.md)`.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `ToggleKey` | string | 'j' | Letter or digit that toggles the console with ALT (CTRL on OS X). An unknown key uses `j`'s key code |

## Key Methods

```php
$logger->getToggleKey(): string
$logger->setToggleKey(string $v): void
```

## Template Usage

```xml
<!-- Toggle console with ALT+L: -->
<com:TJavascriptLogger ToggleKey="L" />
```

## Rendering

`onPreRender()` raises `OnPreRender`, registers the `logger` script package, and registers a begin script with the key code of `ToggleKey` (76 for `L`):

```js
var logConsole; jQuery(function() { logConsole = new LogConsole(76)});
```

The control renders a `<div>` with the usage text and a link that toggles the console.

## Patterns & Gotchas

- **Requires jQuery** — the embedded console uses jQuery. Ensure jQuery is registered on the page (PRADO includes it via `TClientScriptManager`).
- **`ToggleKey`** — a single character. The ALT key is always the modifier. The shortcut is case-insensitive.
- **Development tool** — this control is intended for development/debugging. Remove it from production pages or conditionalize on `TApplication::getMode()`:
  ```php
  $this->jsLogger->Visible = ($this->getApplication()->getMode() === TApplicationMode::Debug);
  ```
- **`logConsole` global** — the JavaScript variable `logConsole` is exposed globally. Client-side code can call `logConsole.log('message')` to write to the console panel.
