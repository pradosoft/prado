# TLabel

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TLabel](./TLabel.md)

**Location:** `framework/Web/UI/WebControls/TLabel.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TLabel displays a piece of text on a web page and optionally associates with another control via the HTML `<label for="">` mechanism. When `ForControl` is set, TLabel renders a `<label>` element whose `for` attribute is set to the client ID of the associated control; if `ForControl` is empty it renders a `<span>`. If `ForControl` is set and the target control is not visible, the label itself is also not rendered.

TLabel implements `IDataRenderer` so it can participate in data-binding scenarios (`Data` property aliases `Text`).

TLabel is the base class for `TBaseValidator` and therefore all validator controls. The `ForControl` property is **blocked** (throws `TNotSupportedException`) in `TBaseValidator`.

**Critical:** `Text` is NOT HTML-encoded before rendering. Avoid putting untrusted input directly into `Text`.

## Inheritance

`TLabel` → `TWebControl` → `TControl` → `TComponent`

Implements: `IDataRenderer`

## Key Constants / Enums

None specific to TLabel.

## Key Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `Text` | string | `''` | Text to display. Rendered verbatim — NOT HTML-encoded. If empty, the body content of the label tag is rendered instead. |
| `ForControl` | string | `''` | ID of the associated control (within the label's naming container). When set, renders `<label for="clientId">` and suppresses the label if the target is invisible. |

## Key Methods

| Method | Description |
|---|---|
| `getText()` / `setText($value)` | Get/set label text (stored in viewstate). |
| `getData()` / `setData($value)` | Aliases for `getText()`/`setText()`. Required by `IDataRenderer`. |
| `getForControl()` / `setForControl($value)` | Get/set the associated control ID. |
| `getTagName()` | Returns `'label'` when `ForControl` is set, `'span'` otherwise. |
| `render($writer)` | Overrides parent to implement the visibility check: resolves `ForControl` → checks target visibility → only renders if visible. Throws `TInvalidDataValueException` if `ForControl` is set but the control cannot be found. |
| `renderContents($writer)` | Writes `Text` directly if non-empty; otherwise falls back to rendering child controls. |
| `addAttributesToRender($writer)` | Adds the `for` attribute (using the resolved client ID) when `ForControl` is non-empty. |

## Events

None beyond those inherited from `TWebControl`.

## Patterns & Gotchas

- **`<label>` vs `<span>` tag** — The HTML tag changes dynamically based on whether `ForControl` is set. If `ForControl` is set but the referenced control cannot be found, a `TInvalidDataValueException` is thrown at render time.
- **Visibility coupling** — When `ForControl` is set, TLabel delegates its own rendering visibility to the associated control's `getVisible(true)`. If the target is hidden (by any ancestor), the label disappears too.
- **`for` attribute uses client ID** — The `for` attribute is set to `$control->getClientID()`, not the template `ID`. This is the namespaced form used in the DOM.
- **Text is not encoded** — TLabel writes `Text` directly into the HTML stream. For user-supplied content, HTML-encode before assigning to `Text`.
- **Body content fallback** — If `Text` is empty, child controls in the label's template are rendered as its content. Use this for labels containing mixed content (e.g., text + a `<span>`).
- **Used as base by validators** — `TBaseValidator` extends TLabel. It overrides `setForControl()` to throw an exception, inherits `Text`/`ErrorMessage` rendering logic, and reuses label's `addAttributesToRender` plumbing.
- **`IDataRenderer`** — Useful in `TRepeater`/`TDataList` scenarios where a renderer class is expected to expose a `Data` property.
