# Web/UI/ActiveControls/TActiveUpdateMode

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveUpdateMode`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveUpdateMode.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Extends:** `TEnumerable`

## Overview
How a callback response updates the markup of an active control that renders again (TActivePanel, TActiveDataGrid, TActiveRepeater, ...).

| Value | Result |
|---|---|
| `Default` | The page's `CallbackUpdateMode` ([TPage](../TPage.md)); the default of `ActiveControl.UpdateMode` |
| `Replace` | `outerHTML`/`innerHTML`: new nodes; the default of `TPage.CallbackUpdateMode` |
| `Morph` | idiomorph patches the existing nodes, which keep their listeners, focus, caret, and scroll position |

## Usage

```xml
<com:TActivePanel ID="Results" ActiveControl.UpdateMode="Morph">
```

```xml
<%@ Page CallbackUpdateMode="Morph" %>
```

A skin sets `ActiveControl.UpdateMode` for every control of a type. `TBaseActiveControl::getIsMorphUpdate()` resolves Default against the page.

## Morph Pipeline

1. `TActiveControlAdapter::render()` registers the `morph` script package (idiomorph) for a morphing control, in a full page or a callback.
2. The control's callback render calls `TCallbackClientScript::replaceContent($element, $writer, true, $morph)`; the client function gets a fifth argument, `true`.
3. `Prado.Element.replace()` calls `Prado.Element.morph()`, the only code that calls idiomorph. Without idiomorph it replaces.

## Client Rules (`Prado.Element.morph`)

| Condition | Result |
|---|---|
| `data-prado-morph="skip"` on an element | The element and its subtree are left as they are |
| New `<script>`, or one whose text changed | Runs once with the page CSP nonce, in document order; inert (`type="text/x-prado-morph"`, original in `data-prado-script-type`) until then |
| Unchanged `<script>` | Does not run again |
| Focused field, unchanged since the callback was sent | Takes the server value; keeps focus and caret |
| Focused field the user changed after the callback was sent | Keeps what the user typed |

`Prado.CallbackRequest.dispatch()` records `Prado.Element.getFocusSnapshot()` as `focusSnapshot`; `replace()` runs with the request as `this` and passes it on.

## Gotchas

- **Rows pair by position.** Data-bound item IDs follow the item index (`rows_ctl1_value` becomes `rows_ctl2_value` after an insert at the top). idiomorph pairs by id, so a morphed row node follows the position, not the record; client-side row state (a class, a listener) stays at its position. Server-rendered state is the same as with Replace.
  - *Keyed matching (not implemented):* render a `data-prado-key` from `DataKeyField` on each item and have the adapter pair items by key, remapping the ids around the morph, since idiomorph pairs only by id.
- **Client control wrappers.** The element stays, so the old `Prado.WebUI.Control` wrapper must remove its listeners when the callback registers a new one. Listeners go through `observe()`; TRatingList moved to it in 4.4.0.
- **Speed.** A 500-row repeater morphs in 10–30 ms; a replace takes 2–6 ms.

## Tests

- `tests/unit/Web/UI/ActiveControls/TActiveUpdateModeTest.php`
- `tests/js/prado/morph.test.js`
- `tests/playwright/web/TCallbackMorphTestCase.spec.js` with `tests/harness/web/protected/pages/CallbackMorphTest.page` (Chromium, Firefox, WebKit; includes the accessibility audit: focus and caret kept, unchanged live region untouched)

## See Also

- [TBaseActiveControl](./TBaseActiveControl.md)
- [TCallbackClientScript](./TCallbackClientScript.md)
- [packages](../../Javascripts/packages.md)

**@since 4.4.0**
