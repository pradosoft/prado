# Web/UI/WebControls/TTooltip

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTooltip`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTooltip.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TWebControl](./TWebControl.md)

## Overview
TTooltip (@since 4.4.0, #1401) renders a `<div popover="hint" role="tooltip">` that describes its `ForControl` target. The client control `Prado.WebUI.TTooltip` (`controls/tooltip.js`, package `tooltip`) shows it next to the target, anchored by `showPopover({source})`, while the pointer is over the target or the tooltip, or the target has focus. It adds the tooltip ID to the target's `aria-describedby`.

## Template Usage

```xml
<com:TLabel ForControl="Password" Text="Password" />
<com:TTextBox ID="Password" TextMode="Password" />
<com:TTooltip ForControl="Password" Text="12 or more characters" />
```

## Key Properties

| Property | Default | Description |
|---|---|---|
| `ForControl` | `''` | Control ID resolved by `findControl()` to its ClientID; otherwise an HTML element ID. Empty registers no client control |
| `Text` | `''` | The tooltip text, when the body is empty; `Encode` HTML-encodes it |
| `Placement` | `Top` | `data-placement` ([TPopoverPlacement](./TPopoverPlacement.md)) |
| `ShowDelay` | `300` | Milliseconds of hover before it shows; focus shows it at once |
| `HideDelay` | `150` | Milliseconds before it hides once the pointer and the focus leave |
| `CssUrl` | `default` | `'default'` publishes `tooltip.css`; `''` registers none |

`Popover` is always `hint`, and `role="tooltip"` overrides `Role`.

## WCAG 1.4.13 Content on Hover or Focus

| Requirement | Behavior |
|---|---|
| Dismissible | Esc hides a showing tooltip. The keydown is prevented and stopped, so an enclosing dialog or popover stays open; the next Esc closes it |
| Hoverable | The pointer crosses from the target onto the tooltip within `HideDelay`, and the tooltip stays |
| Persistent | It shows until both the pointer and the focus leave, or Esc |

## Rendering

- `class="prado-tooltip"` (`TTooltip::CSS_CLASS`) renders before `CssClass`; a duplicate in `CssClass` is dropped.
- `tooltip.css` colors it with `CanvasText` on `Canvas`, so it inverts the page colors and follows forced colors.
- WebKit 26.4 treats `hint` as `manual`; the client control shows and hides it in every browser, so the behavior is the same.
- The tooltip holds text. Interactive content belongs in a [TPopover](./TPopover.md).

## Tests
- `tests/unit/Web/UI/WebControls/TTooltipTest.php`
- `tests/js/controls/tooltip.test.js`
- `tests/playwright/web/TooltipTestCase.spec.js` with `tests/harness/web/protected/pages/TooltipTest.page`

## See Also
- [TPopover](./TPopover.md)
- [TPopoverPlacement](./TPopoverPlacement.md)

**@since 4.4.0**
