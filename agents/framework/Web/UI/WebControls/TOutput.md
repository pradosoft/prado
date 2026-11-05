# Web/UI/WebControls/TOutput

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TOutput`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TOutput.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TWebControl`
**Implements:** `IDataRenderer`

## Overview
TOutput renders the HTML5 `<output>` element: the result of a calculation or user action. [TActiveOutput](../ActiveControls/TActiveOutput.md) updates it during a callback.

## Template Usage

```xml
<com:TTextBox ID="Price" TextMode="Number" />
<com:TTextBox ID="Quantity" TextMode="Number" />
<com:TLabel ForControl="Total" Text="Total" />
<com:TOutput ID="Total" ForControl="Price Quantity" Text="0.00" />
```

## Properties

| Property | Description |
|---|---|
| `Text` / `Data` | Result, HTML-encoded with `THttpUtility::htmlEncode()` (`&` passes, so entities decode). Empty → body content renders. |
| `ForControl` | Control IDs separated by spaces or commas; renders `for` with their client IDs. Invisible controls are left out; a missing control throws `output_forcontrol_invalid`. |
| `ForControlIDs` | Read-only array of the parsed `ForControl` IDs. |

- `name` always renders as the `UniqueID`, so the output is in `form.elements`. The browser does not post `<output>`, and Prado's callback serializes only `input`, `select`, and `textarea`.
- `form` is not a property; `Attributes.form` passes through.

## Accessibility

| Condition | Result |
|---|---|
| Any render | `status` role: polite live region, `aria-atomic` true; the whole content is announced on change. |
| `TLabel ForControl="<output>"` | Names the output (`<output>` is labelable). |
| `ForControl` | Relates the output to its sources; does not name or describe it. |
| Output added or made visible with its result | Not announced; a live region must be in the page before its content changes. |

- Do not add `role="status"`, `aria-live="polite"`, or `aria-atomic="true"`; they duplicate the implicit semantics. `aria-live="off"` stops announcements. `role="alert"` / `aria-live="assertive"` suit urgent messages only.
- Verified with `Accessibility.getFullAXTree` in Chromium (`status`, `live: polite`, `atomic: true`, name from the label, before and after a callback). Playwright's computed tree matches in Firefox and WebKit.
- Screen reader gap (Adrian Roselli, January 2026): VoiceOver with Safari does not announce `<output>` during read-all.

## Tests

- `tests/unit/Web/UI/WebControls/TOutputTest.php`
- `tests/playwright/web/TOutputTestCase.spec.js` with `tests/harness/web/protected/pages/OutputTest.page`

## See Also

- [TActiveOutput](../ActiveControls/TActiveOutput.md)
- [TLabel](./TLabel.md)

**@since 4.4.0**
