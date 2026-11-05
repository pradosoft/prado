# Web/UI/ActiveControls/TActiveOutput

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveOutput`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveOutput.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Extends:** [TOutput](../WebControls/TOutput.md)
**Implements:** [IActiveControl](./IActiveControl.md)

## Overview
Active counterpart of `TOutput`. During a callback, `Text` and `ForControl` changes update the client `<output>`. Raises no callback of its own; a source control's callback sets the result.

## Mechanics

| Setter | Client update |
|---|---|
| `setText()` | `TCallbackClientScript::update()` with the HTML-encoded text; matches the page render. |
| `setForControl()` | `setAttribute('for', ...)` with the visible controls' client IDs; `removeAttribute('for')` when none remain. |

- An unchanged value sends nothing. Updates require `ActiveControl.EnableUpdate` and a callback after `OnLoad` (`canUpdateClientSide()`).
- `getEnsureId()` returns true; the client locates the element by its ID.

## Accessibility

- The update replaces the content, not the element; the polite live region announces the new result.
- The element renders when `Text` is empty, so it is in the page before the first result.
- Attributes not to add: see [TOutput](../WebControls/TOutput.md).

## Tests

- `tests/unit/Web/UI/ActiveControls/TActiveOutputTest.php`
- `tests/playwright/web/TOutputTestCase.spec.js`

**@since 4.4.0**
