# Web/UI/ActiveControls/TActiveSvg

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveSvg`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveSvg.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Extends:** [TSvg](../WebControls/TSvg.md)
**Implements:** [IActiveControl](./IActiveControl.md)

## Overview
Active counterpart of `TSvg`. During a callback, a change to the content or an SVG attribute property re-renders the graphic once, and the client morphs it into the existing `<svg>` ([TActiveUpdateMode](./TActiveUpdateMode.md)). Unchanged nodes keep their identity and listeners. Raises no callback of its own.

## Mechanics

| Change in a callback | Client update |
|---|---|
| `Svg`, `SvgFile` | re-render |
| `ViewBox`, `PreserveAspectRatio`, `Width`, `Height` | re-render |
| `AlternateText`, `Description`, `Decorative` | re-render |
| the same value | none |
| `render($param->getNewWriter())` | re-render; for template content |

- A changed setter calls `registerUpdate()`, which defers `render()` to the callback response through `TActivePageAdapter::registerControlToRender()` with a new writer, as `TActiveRepeater::setDataSource()` does. The adapter keys by unique ID, so several changes send one update.
- `render()` after pre-render renders and calls `TCallbackClientScript::replaceContent($this, $writer, true, getIsMorphUpdate())`; before pre-render it registers the deferred render.
- `registerUpdate()` sets `EnableUpdate` false on the active controls inside, as `TActivePanel` does.
- The constructor sets `ActiveControl.UpdateMode` to `Morph`, so the page's `CallbackUpdateMode` (Replace by default) does not apply. `Replace` replaces the element; `Default` follows the page. `TActiveControlAdapter` registers the `morph` script package.
- `onPreRender()` runs in a callback, so new content is processed then: hoisted styles go out in the callback's stylesheet header, and IDs are prefixed with the client ID.
- No hash of the rendered markup: the page state is saved before the callback render, so a hash cannot persist to the next request. The setter comparison and the morph, which leaves unchanged nodes alone, cover unchanged content.
- `getEnsureId()` returns true.

## Accessibility

- The update keeps `role="img"`, `aria-labelledby`, and the `<title>`; a new `AlternateText` renames the graphic, and the title node is kept.
- `Decorative` toggles `aria-hidden` and removes or restores the title.
- The graphic is not a live region; a screen reader does not announce the change. Pair it with [TActiveOutput](./TActiveOutput.md) to report it.

## Tests

- `tests/unit/Web/UI/ActiveControls/TActiveSvgTest.php`
- `tests/playwright/web/TActiveSvgTestCase.spec.js` with `tests/harness/web/protected/pages/ActiveSvgTest.page`: kept nodes and listeners, the Replace graphic's new nodes, no mutation for unchanged content, new hoisted styles, Decorative, template content, and the Chromium accessibility tree

## See Also

- [TSvg](../WebControls/TSvg.md)
- [TActiveUpdateMode](./TActiveUpdateMode.md)
- [TActivePanel](./TActivePanel.md)

**@since 4.4.0**
