# TKeyboard

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TKeyboard](./TKeyboard.md)

**Location:** `framework/Web/UI/WebControls/TKeyboard.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

Renders a virtual on-screen keyboard attached to a text input control. When the user focuses the associated input, the keyboard widget appears. Clicking keys inserts characters into the input without requiring a physical keyboard.

JavaScript: `controls/keyboard.js` (published via `TAssetManager`).

Extends `[TWebControl](./TWebControl.md)`.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `ForControl` | string | '' | **Required.** ID of the `TTextBox` (or input control) this keyboard attaches to |
| `AutoHide` | bool | true | Hide the keyboard when the input loses focus |
| `KeyboardCssClass` | string | `Keyboard` | CSS class name for the keyboard `<div>` element |
| `CssUrl` | string | '' | URL to a custom CSS stylesheet (overrides default) |

## Key Methods

```php
$kb->getForControl(): string
$kb->setForControl(string $v): void   // required before rendering
$kb->getAutoHide(): bool
$kb->setAutoHide(bool $v): void
$kb->getKeyboardCssClass(): string
$kb->setKeyboardCssClass(string $v): void
$kb->getCssUrl(): string
$kb->setCssUrl(string $v): void
```

## Template Usage

```xml
<com:TTextBox ID="searchBox" />
<com:TKeyboard ForControl="searchBox" />
```

```xml
<!-- Custom CSS and no auto-hide: -->
<com:TKeyboard ForControl="nameInput"
               AutoHide="false"
               CssUrl="assets/custom-keyboard.css"
               KeyboardCssClass="MyKeyboard" />
```

## Client Script Registration

On `onPreRender`, the keyboard:
1. Publishes keyboard assets (JS + default CSS) via `TAssetManager`.
2. Registers a JavaScript instantiation:
   ```js
   new Prado.WebUI.TKeyboard(options);
   ```
   where `options` includes the associated input's client ID, auto-hide setting, and CSS class.

## Patterns & Gotchas

- **`ForControl` is required** — without it, the keyboard cannot attach to any input. The referenced control must be a sibling or accessible within the same naming container.
- **Custom CSS** — set `CssUrl` and `KeyboardCssClass` together when changing the appearance. The JS uses the CSS class name to identify and show/hide the keyboard element.
- **Mobile devices** — native virtual keyboards are generally more appropriate on mobile. Consider hiding `TKeyboard` for mobile users (CSS media query or JS detection).
- **`AutoHide=false`** — keyboard stays visible until the user explicitly dismisses it. Useful for kiosk-style applications.
