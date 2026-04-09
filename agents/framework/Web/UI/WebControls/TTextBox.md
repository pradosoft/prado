# TTextBox

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TTextBox](./TTextBox.md)

**Location:** `framework/Web/UI/WebControls/TTextBox.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TTextBox renders an HTML `<input>` or `<textarea>` element for user text input. The rendered tag depends on `TextMode`: `MultiLine` produces `<textarea>`; all other modes produce `<input type="...">`. TTextBox implements `IPostBackDataHandler` (reads posted values), `IValidatable` (works with validators), and `IDataRenderer` (`Data` property aliases `Text`).

**Critical:** The `Text` property is stored and rendered as-is — it is NOT HTML-encoded. Use `getSafeText()` before displaying user-supplied content to prevent XSS.

## Inheritance

`TTextBox` → `TWebControl` → `TControl` → `TComponent`

Implements: `IPostBackDataHandler`, `IValidatable`, `IDataRenderer`

## Key Constants / Enums

**`TTextBoxMode`** (used by `TextMode` property):
- `SingleLine` — `<input type="text">` (default)
- `MultiLine` — `<textarea>`
- `Password` — `<input type="password">` (value not sent back to browser unless `PersistPassword=true`)
- HTML5 types: `Color`, `Date`, `Datetime`, `DatetimeLocal`, `Email`, `Month`, `Number`, `Range`, `Search`, `Tel`, `Time`, `Url`, `Week`

**`TTextBoxAutoCompleteType`** (used by `AutoCompleteType` property):
- `None` — no autocomplete attribute rendered (default)
- `Enabled` — `autocomplete="on"`
- `Disabled` — `autocomplete="off"`

**Constants:**
- `DEFAULT_ROWS = 4` — default row count for MultiLine mode
- `DEFAULT_COLUMNS = 20` — default column count for MultiLine mode

## Key Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `Text` | string | `''` | Text content. NOT HTML-encoded. Stored in viewstate. |
| `TextMode` | TTextBoxMode | `SingleLine` | Rendering mode / input type |
| `AutoTrim` | bool | `false` | If true, trims whitespace from posted value before storing |
| `AutoPostBack` | bool | `false` | Posts back when user leaves the field |
| `CausesValidation` | bool | `true` | Whether postback triggers validation |
| `ValidationGroup` | string | `''` | Validation group for AutoPostBack |
| `ReadOnly` | bool | `false` | Renders `readonly` attribute; posted value is ignored |
| `MaxLength` | int | `0` | `maxlength` attribute (0 = not set) |
| `Columns` | int | `0` | `size` attribute for SingleLine; `cols` for MultiLine (0 = use DEFAULT_COLUMNS for MultiLine) |
| `Rows` | int | `4` | `rows` attribute for MultiLine only |
| `Wrap` | bool | `true` | Whether MultiLine text wraps. `false` adds `wrap="off"` (not XHTML-compatible) |
| `PersistPassword` | bool | `false` | If true, the password value is re-sent to the browser on postback |
| `AutoCompleteType` | TTextBoxAutoCompleteType | `None` | Browser autocomplete hint |
| `EnableClientScript` | bool | `true` | Whether JS postback/event handling is registered |
| `IsValid` | bool | `true` | Set by validators; readable after validation runs |

## Key Methods

| Method | Description |
|---|---|
| `getText()` / `setText($value)` | Get/set raw text content (no encoding). Resets the cached SafeText. |
| `getData()` / `setData($value)` | Aliases for `getText()`/`setText()`. Required by `IDataRenderer`. |
| `getSafeText()` | Returns text purified by HTMLPurifier (strips JS, malicious markup). Cached per-text value. Use when displaying user input. |
| `setConfig(HTMLPurifier_Config)` | Provides a custom HTMLPurifier configuration for `getSafeText()`. |
| `getConfig()` | Returns the HTMLPurifier config, creating a default one (with runtime serializer path) if not set. |
| `loadPostData($key, $values)` | Reads posted value; applies `AutoTrim` if set; ignores value if `ReadOnly`. Returns whether value changed. |
| `raisePostDataChangedEvent()` | Triggers validation (if `AutoPostBack` + `CausesValidation`) then raises `OnTextChanged`. |
| `getValidationPropertyValue()` | Returns `getText()`. Used by validators. |
| `getDataChanged()` | Whether the posted value differed from the stored value. |

## Events

| Event | Raised When |
|---|---|
| `OnTextChanged` | Text value changed on postback (via `raisePostDataChangedEvent`) |

## Patterns & Gotchas

- **Text is not HTML-encoded** — Never echo `Text` directly into HTML without escaping. Use `getSafeText()` for user-supplied content or `THttpUtility::htmlEncode($textBox->getText())` for simple escaping.
- **MultiLine rendering** — Renders as `<textarea>`. The body content is HTML-encoded via `THttpUtility::htmlEncode`. An extra `\n` is written after the opening tag to match browser normalization.
- **Password mode** — The value is never sent back to the browser as HTML unless `PersistPassword=true`. `loadPostData` still reads the submitted value normally.
- **AutoTrim** — Applied during `loadPostData`, so it affects the stored `Text` value for the rest of the request.
- **`Wrap=false`** — Adds `wrap="off"` which is not valid XHTML. No XHTML-compatible alternative exists.
- **`AutoCompleteType`** with `Enabled`/`Disabled` — Also not XHTML-compatible.
- **SafeText caching** — The HTMLPurifier result is cached in `$_safeText`. Calling `setText()` clears the cache. The HTMLPurifier instance is shared statically across all TTextBox instances.
- **Client JS** — Registered only when `EnableClientScript=true`, the control is enabled, and either `AutoPostBack=true` or `TextMode=SingleLine`.
- **`Columns` meaning differs by mode** — In SingleLine/Password it becomes the `size` attribute; in MultiLine it is the `cols` attribute with a 20-column fallback.
