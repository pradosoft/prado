# Web/UI/WebControls/TSuggestionList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSuggestionList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSuggestionList.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TSuggestionList renders the HTML5 `<datalist>` element: predefined values the browser suggests for an input. The input stays free-text. A `TTextBox` uses the list through `SuggestionList`, which renders the input's `list` attribute with the list's ClientID.

The name avoids `TDataList`, the templated repeater; PHP class names are case-insensitive.

Extends [`TListControl`](./TListControl.md). No client script.

## Rendering

| Item state | Output |
|---|---|
| Enabled, Text = Value | `<option value="V"></option>` |
| Enabled, Text ≠ Value | `<option value="V">Text</option>` (Text HTML-encoded) |
| Disabled, or Value = `''` | not rendered |
| Item attributes | rendered on `<option>`, except `Group` (no `<optgroup>` in a datalist); attributes are not mutated |

- `getEnsureId()` always returns `true`; the `id` always renders.
- `addAttributesToRender()` calls `TWebControl::addAttributesToRender()` directly, skipping `TListControl`'s `ensureRenderInForm`, `multiple`, and AutoPostBack script. The list renders outside a form.
- Selection, `PromptText`/`PromptValue`, and `AutoPostBack` have no effect.

## Data Binding

`performDataBinding()` is its own implementation; no selection caching, no `DataGroupField`.

| Data row | Value | Text |
|---|---|---|
| scalar, integer key (`['Paris']`) | the scalar | the scalar |
| scalar, string key (`['NYC' => 'New York City']`) | the key | the scalar |
| row, no fields set | column `1` | column `0` (TListControl pairing) |
| row, only `DataTextField` | `DataTextField` | `DataTextField` |
| row, both fields | `DataValueField` | `DataTextField` |

`DataTextFormatString` formats the Text (label) only; the value stays raw. The scalar rule matches `TDataBoundControl::validateDataSource()` for string data sources. TListControl itself binds a scalar's key as its value, which would suggest `0`, `1`, ….

## Template Usage

```xml
<com:TTextBox ID="City" SuggestionList="Cities" />
<com:TSuggestionList ID="Cities">
  <com:TListItem Text="Paris" />
  <com:TListItem Value="NYC" Text="New York City" />
</com:TSuggestionList>
```

## TTextBox Integration

- `TTextBox::SuggestionList` — ID resolved with `findControl()` at render; missing → `TInvalidDataValueException` `textbox_suggestionlist_invalid`.
- `list` renders for every `TextMode` except `MultiLine` (`<textarea>`) and `Password`.
- An invisible list (`getVisible(true)` false) renders no `list` attribute.
- Any control can be the target; only its ClientID is used.

## Accessibility

- Browsers expose the input as a combobox whose options are the suggestions; no ARIA is added.
- Suggestions supplement the input's visible label (`TLabel ForControl`); they do not replace it.
- Browser support varies by input type (some browsers ignore `list` on `color` and date inputs), so the input must accept typed values. Validate on the server; a datalist does not constrain the value.
- The popup is browser UI: it follows the platform's zoom and contrast settings and takes no page CSS.

## Gotchas

- Prado's `Prado.WebUI.TTextBox` Enter-key handler only validates or raises `change`; it does not conflict with choosing a suggestion.
- No active (callback) variant yet: items changed in a callback do not update the client.
