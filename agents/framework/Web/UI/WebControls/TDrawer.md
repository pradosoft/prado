# Web/UI/WebControls/TDrawer

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDrawer`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDrawer.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TDialog](./TDialog.md)

## Overview
TDrawer (@since 4.4.0, #1401) is a `<dialog>` at a page edge, the off-canvas pattern. A TButton invoker opens it modally without script. `DockAt` docks it in the page layout on wide screens. The client control `Prado.WebUI.TDrawer` (`controls/drawer.js`, package `drawer`) adds the dock switch, swipe-to-close, and the behavior of ClosedBy Any and None where the browser lacks `closedby`.

## Template Usage

```xml
<com:TButton Text="Menu" CommandFor="Nav" Command="show-modal" />
<com:TDrawer ID="Nav" HeaderText="Menu" DockAt="64rem">
	<nav aria-label="Main">...</nav>
</com:TDrawer>
```

## Key Properties

| Property | Default | Description |
|---|---|---|
| `Placement` | `Start` | `data-placement` ([TDrawerPlacement](./TDrawerPlacement.md)) |
| `DockAt` | `''` | A positive width in `px`, `rem`, or `em`; at or above it a Start or End drawer docks. `''` never docks; Top and Bottom ignore it (`getDockWidth()` is `''`) |
| `Swipe` | `true` | A touch swipe toward the edge closes it; off with `ClosedBy` None and while docked |
| `ClosedBy` | `Any` | Overrides the TDialog default `Auto`. A docking drawer applies it only in modal mode |
| `ShowCloseButton` | `true` | Overrides the TDialog default `false`; the single-pointer alternative to the swipe (WCAG 2.5.1) |
| `CssUrl` | `default` | `'default'` publishes `dialog.css` and `drawer.css`, always; `''` registers none |

## Docking

| Width | Script | Rendering |
|---|---|---|
| at or above DockAt | any | open non-modal dialog in the page flow; its invokers hide |
| below DockAt | on | closed; an invoker opens it modally |
| below DockAt | off | open dialog inline in the page flow; its invokers hide |

The server renders a docking drawer `open` with no `closedby`:

- `closedby` Auto is None for a non-modal dialog, so a click on the page and Esc leave the docked drawer open.
- `show()` is not called on load, so the docked drawer takes no focus.
- `getDockCss()` registers rules scoped to `#{ClientID}` by DockAt through `registerStyleSheet()`, using `@media (scripting: ...)`. `[commandfor="{ClientID}"]` hides the invokers, the close button included.
- A script directly after `</dialog>` (`getNarrowScript()`, with the CSP nonce through `TJavaScript::renderScriptBlocks()`) closes the drawer on a narrow screen, applies `closedby`, and with `Open` calls `showModal()`. The invoker works before the page scripts load, because an open non-modal dialog ignores `show-modal`.
- The client control re-applies the state when the width crosses DockAt. `show()` runs only then.

The first-paint audit in #1401 compares this with rendering closed, rendering `closedby`, and showing a closed dialog with CSS.

## Swipe

Touch events carry the swipe, because `preventDefault()` on `touchmove` decides per gesture, where `touch-action` is fixed before the gesture starts and would stop a bottom sheet's content from scrolling. Past a 10 px slop, a move along the closing direction with no content scrolling that way starts the swipe; any other gesture stays with the browser. The panel follows the finger, then `requestClose()` runs past a third of its size or at 0.5 px/ms; otherwise it snaps back. A `cancel` handler can keep it open. A bottom sheet closes on a downward swipe only when its content is scrolled to the top. There is no edge swipe to open, which conflicts with the OS back gesture.

## Accessibility
- Modal: the page behind is inert, the first focusable element or `AutoFocus` takes the focus, and closing returns it to the invoker.
- Docked: the element keeps the `dialog` role, since `<dialog>` allows only `alertdialog` as an override. A `<nav>` in the body gives navigation its landmark. How a screen reader announces a docked dialog is unchecked.
- Motion: `prefers-reduced-motion` removes the slide transition.
- WebKit 26.4 lacks `closedby`: the client control closes on a backdrop click for Any and cancels Esc for None.

## Gotchas
- The drawer docks where the template puts it; the page layout gives it a column.
- `Modal` has no effect on a docking drawer: `Open` opens it modally below DockAt.
- Firefox does not support the `overlay` transition, so the drawer closes there without the slide out.

## Tests
- `tests/unit/Web/UI/WebControls/TDrawerTest.php`
- `tests/js/controls/drawer.test.js`
- `tests/playwright/web/DrawerTestCase.spec.js` with `tests/harness/web/protected/pages/DrawerTest.page`

## See Also
- [TDialog](./TDialog.md)
- [TButton](./TButton.md): `CommandFor`, `Command`

**@since 4.4.0**
