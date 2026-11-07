# Web/UI/WebControls/TContextMenu

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TContextMenu`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TContextMenu.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TDropdownMenu](./TDropdownMenu.md)

## Overview
TContextMenu (@since 4.4.0, #1401) is an action menu that opens on its target in place of the browser context menu. It is a TDropdownMenu without a menu button; `Prado.WebUI.TContextMenu` (`controls/menu.js`, a `TDropdownMenu` subclass) opens it and gives it the menu roles and keyboard. It needs script; without it, the browser context menu shows.

## Template Usage

```xml
<com:TPanel ID="Photo" TabIndex="0">...</com:TPanel>
<com:TContextMenu ForControl="Photo">
	<com:TButton Text="Rotate" ButtonTag="Button" OnClick="rotate" />
	<com:TButton Text="Delete" ButtonTag="Button" OnClick="delete" />
</com:TContextMenu>
```

## Key Properties

| Property | Description |
|---|---|
| `ForControl` | Control ID resolved by `findControl()` to its ClientID; otherwise an HTML element ID. Empty registers no client control |
| `ButtonText` | Always `''`; no menu button renders |
| `Placement` | Not rendered; the client control sets `inset`, `left`, and `top` inline |

## Opening

| Action on the target | Position |
|---|---|
| right-click, long press | the pointer |
| a `contextmenu` event from the keyboard (`button` -1, or at 0,0) | below the start of the target |
| Shift+F10, the ContextMenu key (keydown, prevented) | below the start of the target |

- Where `contextmenu` fires while the button or finger is down (macOS, Linux, touch), the menu opens after the `pointerup`. Opened sooner, that `pointerup` outside the menu would light dismiss it.
- The menu moves back into the viewport when it does not fit, and focuses its first item. `showPopover({source})` names the target.
- Firefox and WebKit do not fire `contextmenu` for Shift+F10, and macOS has no ContextMenu key, so the target handles both keys.
- A target with no focusable element takes `TabIndex="0"` so the keyboard reaches it. Esc returns the focus to it.

## Tests
- `tests/unit/Web/UI/WebControls/TContextMenuTest.php`
- `tests/js/controls/menu.test.js` (`Prado.WebUI.TContextMenu`)
- `tests/playwright/web/ContextMenuTestCase.spec.js` with `tests/harness/web/protected/pages/ContextMenuTest.page`

## See Also
- [TDropdownMenu](./TDropdownMenu.md)
- [TPopover](./TPopover.md)

**@since 4.4.0**
