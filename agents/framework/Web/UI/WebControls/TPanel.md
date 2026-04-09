# TPanel

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TPanel](./TPanel.md)

**Location:** `framework/Web/UI/WebControls/TPanel.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TPanel is a container control that renders a `<div>` element. It acts as a grouping wrapper for other controls and is commonly used to show/hide entire regions, set a default submit button for a section of a form, or render a `<fieldset>` with a legend.

When `GroupingText` is set, TPanel wraps its contents in a `<fieldset><legend>` structure inside the outer `<div>`. When `DefaultButton` is set, pressing Enter within the panel fires that button's click event via a registered JavaScript handler.

## Inheritance

`TPanel` → `TWebControl` → `TControl` → `TComponent`

## Key Constants / Enums

None specific to TPanel.

## Key Properties

Properties fall into two groups: those stored directly on TPanel and those delegated to `TPanelStyle` (accessed via `getStyle()`).

**Direct properties:**

| Property | Type | Default | Description |
|---|---|---|---|
| `DefaultButton` | string | `''` | ID path to the button that is "clicked" when the user presses Enter inside the panel. Resolved via `findControl()`. |
| `GroupingText` | string | `''` | When non-empty, wraps the panel body in `<fieldset><legend>GroupingText</legend>...</fieldset>` inside the `<div>`. |

**Style-delegated properties (via `TPanelStyle`):**

| Property | Values | Description |
|---|---|---|
| `Wrap` | bool (default `true`) | Whether body content wraps |
| `HorizontalAlign` | `NotSet`, `Justify`, `Left`, `Right`, `Center` | Horizontal alignment of content |
| `Direction` | `NotSet`, `LeftToRight`, `RightToLeft` | Content direction (RTL support) |
| `BackImageUrl` | string | URL of a background image |
| `ScrollBars` | `None`, `Auto`, `Both`, `Horizontal`, `Vertical` | CSS overflow / scroll bar visibility |

## Key Methods

| Method | Description |
|---|---|
| `getTagName()` | Returns `'div'`. |
| `createStyle()` | Returns a `TPanelStyle` instance (overrides TWebControl). |
| `renderBeginTag($writer)` | Renders the `<div>` opening tag. If `GroupingText` is set, also opens `<fieldset>` and `<legend>`. |
| `renderEndTag($writer)` | If `GroupingText` is set, closes `</fieldset>` before closing `</div>`. |
| `render($writer)` | After rendering children, resolves `DefaultButton` and registers it with `TClientScriptManager::registerDefaultButton()`. Throws `TInvalidDataValueException` if the button ID cannot be found. |

## Events

None beyond those inherited from `TWebControl`.

## Patterns & Gotchas

- **`DefaultButton` resolution** — The button is located with `findControl($id)` at render time. If it cannot be found, a `TInvalidDataValueException` is thrown. The panel's client ID is also emitted as an HTML `id` attribute when `DefaultButton` is set (required by the JS handler).
- **`GroupingText` and tag structure** — When `GroupingText` is set, the rendered HTML is `<div ...><fieldset><legend>text</legend>...children...</fieldset></div>`. The outer `<div>` is always present; the fieldset is nested inside it.
- **Show/hide groups** — Set `Visible=false` to hide the entire panel and all its children at once. This is the most common use case.
- **`Direction=RightToLeft`** — Applied by `TPanelStyle` as a CSS `direction` style. Useful for RTL languages.
- **`ScrollBars`** — Applies CSS `overflow` variants. `Auto` maps to `overflow: auto`; `Both` to `overflow: scroll`.
- **Style object** — All style properties (background image, wrap, direction, scrollbars, alignment) are stored in the `TPanelStyle` object, not in viewstate directly on TPanel. Access them via the convenience wrapper properties.
