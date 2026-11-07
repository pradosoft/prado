# Web/UI/WebControls/TDropdownMenu

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDropdownMenu`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDropdownMenu.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TPopover](./TPopover.md)

## Overview
TDropdownMenu (@since 4.4.0, #1401) renders a menu button and an action menu (the WAI-ARIA menu button pattern). The menu is a `TPopover`, so it opens, closes on Esc and a click outside, and returns the focus without script. The client control `Prado.WebUI.TDropdownMenu` (`controls/menu.js`, package `menu`) gives it the `menu` role, the item roles, and the menu keyboard.

## Template Usage

```xml
<com:TDropdownMenu ButtonText="Actions">
	<com:TButton Text="Duplicate" ButtonTag="Button" OnClick="duplicate" />
	<com:TButton Text="Archive" ButtonTag="Button" OnClick="archive" />
	<hr />
	<com:THyperLink Text="Export" NavigateUrl="export.csv" />
</com:TDropdownMenu>
```

## Rendering

- `ButtonText` renders the menu button with `aria-haspopup="menu"` before `type`; the client control adds `aria-haspopup` to any other invoker whose `commandfor` names the menu.
- `class="prado-popover prado-menu"` renders before `CssClass`.
- `CssUrl` `'default'` publishes `popover.css` and `menu.css`; any other value replaces both; `''` registers none.
- `Placement` `Bottom` and `Top` align the menu with the start edge of the button (`position-area: block-end span-inline-end`).

## Client Control

| Item | Set by the client control |
|---|---|
| the popover | `role="menu"` |
| each enabled link, button, `input[type=submit|button]`, or `[role^=menuitem]` | `role="menuitem"` (an explicit `menuitemcheckbox`/`menuitemradio` is kept), `tabindex="-1"` |
| `ul`, `ol`, `li` between the menu and an item | `role="none"` |

| Key | Menu button | Menu |
|---|---|---|
| Enter, Space, click | opens; the `toggle` event focuses the first item | activates the item; the menu closes |
| Down / Up Arrow | opens on the first / last item at once | next / previous item, wrapping |
| Home, End | | first, last item |
| a character | | next item whose text starts with it |
| Esc | | the browser closes it and focuses the button |
| Tab | | closes it; the focus moves on |

The arrows open the menu and focus the item synchronously, so a key typed before the asynchronous `toggle` event is not overridden. A disabled button is skipped. Without script, the menu is a popover of links and buttons in Tab order.

## Tests
- `tests/unit/Web/UI/WebControls/TDropdownMenuTest.php`
- `tests/js/controls/menu.test.js`
- `tests/playwright/web/DropdownMenuTestCase.spec.js` with `tests/harness/web/protected/pages/DropdownMenuTest.page`

## See Also
- [TPopover](./TPopover.md)

**@since 4.4.0**
