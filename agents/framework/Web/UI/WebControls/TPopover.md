# Web/UI/WebControls/TPopover

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPopover`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPopover.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TWebControl](./TWebControl.md)

## Overview
TPopover (@since 4.4.0, #1401) renders a `<div popover>` next to the button that opens it. The browser shows it in the top layer, closes it on Esc and, when `auto`, on a click outside (light dismiss), and returns the focus to the invoker. No script is needed. The bundled stylesheet `assets/popover.css` anchors it with CSS anchor positioning (`position-area`) and flips it when it does not fit (`position-try-fallbacks`).

## Template Usage

```xml
<com:TPopover ID="Help" ButtonText="Help" Placement="End">
	<p>Passwords take 12 or more characters.</p>
</com:TPopover>

<com:TButton Text="Filters" CommandFor="Filters" Command="toggle-popover" />
<com:TPopover ID="Filters">
	<com:TCheckBox ID="Active" Text="Active only" />
</com:TPopover>
```

## Key Properties

| Property | Default | Renders |
|---|---|---|
| `Popover` | `auto` | `popover`: `auto`, `manual`, `hint`; `''` or false restores `auto` |
| `Placement` | `Bottom` | `data-placement` ([TPopoverPlacement](./TPopoverPlacement.md)); `None` renders none and the popover keeps the browser default position |
| `ButtonText` | `''` | a `<button type="button" id="{ClientID}_button" commandfor command="toggle-popover">` before the popover; `Encode` HTML-encodes it |
| `ButtonCssClass` | `''` | the class of that button |
| `CssUrl` | `default` | `'default'` publishes `popover.css`; `''` registers none; any other value is a stylesheet URL |

## Rendering

- The popover always renders its client ID, so an invoker can name it.
- `class="prado-popover"` (`TPopover::CSS_CLASS`) renders before `CssClass`; a duplicate in `CssClass` is dropped. The `prado-` prefix keeps clear of the Bootstrap `.popover` class.
- The anchor is the invoker that opened the popover (`commandfor`, `popovertarget`) or the `source` passed to `showPopover({source})`. Opened without one, an anchored popover has no anchor and `position-area` does not apply.

## Accessibility
- The browser exposes the expanded state of an invoker (`aria-expanded`) from the popover state; the control renders no `aria-expanded`.
- The popover follows its invoker in the Tab order. Esc closes it and returns the focus to the invoker.
- A popover has no role. Content that needs one, such as a labeled group, takes `Role` and `aria-labelledby`.
- WebKit 26.4 does not support `hint`; it treats the value as `manual`.

## Tests
- `tests/unit/Web/UI/WebControls/TPopoverTest.php`
- `tests/playwright/web/PopoverTestCase.spec.js` with `tests/harness/web/protected/pages/PopoverTest.page`

## See Also
- [TButton](./TButton.md): `CommandFor`, `Command`
- [TWebControl](./TWebControl.md): `Popover`

**@since 4.4.0**
