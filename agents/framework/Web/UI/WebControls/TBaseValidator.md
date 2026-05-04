# Web/UI/WebControls/TBaseValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBaseValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBaseValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TBaseValidator is the abstract base class for all validator controls. It extends TLabel and implements `IValidator`. Validators run on every postback (or when `TPage::validate()` is called manually) and check the value of a target input control. If client-side scripting is enabled, validation is also performed in the browser before the form is submitted.

Error display is controlled by the `Display` property. The displayed text is chosen from: `Text` property → `ErrorMessage` property → body content of the validator tag (in that priority order). Error messages also feed into `TValidationSummary` controls via `ErrorMessage`.

Validators self-register with `TPage::getValidators()` on `onInit` and remove themselves on `onUnload`.

## Inheritance

`TBaseValidator` → `TLabel` → `TWebControl` → `TControl` → `TComponent`

Implements: `IValidator`

## Key Constants / Enums

**`TValidatorDisplayStyle`** (used by `Display` property):
- `Fixed` — space is reserved in the layout even when valid (default)
- `Dynamic` — element is `display:none` when valid (collapses in layout)
- `None` — never rendered (used with `TValidationSummary` only)

## Key Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `ControlToValidate` | string | `''` | Dot-path ID of the input control to validate (relative to the validator's naming container) |
| `ErrorMessage` | string | `''` | Text shown in `TValidationSummary`; also shown in the validator if `Text` is empty |
| `Text` | string | `''` | Text displayed inline by the validator when invalid (inherited from TLabel) |
| `Display` | TValidatorDisplayStyle | `Fixed` | How the validator element behaves in layout when valid |
| `EnableClientScript` | bool | `true` | Whether to emit JavaScript for browser-side validation |
| `IsValid` | bool | `true` | Whether the last validation passed; set to `true` automatically when disabled |
| `ValidationGroup` | string | `''` | Group name; only validators in the same group fire when the triggering control specifies a group |
| `FocusOnError` | bool | `false` | Whether the browser moves focus to the validated control on failure |
| `FocusElementID` | string | `''` | Client ID of the element to focus; defaults to the validated control's client ID |
| `ControlCssClass` | string | `''` | CSS class added to the target control when it fails validation; removed on success |

Note: `ForControl` (inherited from TLabel) is **not supported** and throws `TNotSupportedException` if set.

## Key Methods

| Method | Description |
|---|---|
| `validate()` | Runs validation. Calls `evaluateIsValid()` internally. Returns `bool`. Do not override — override `evaluateIsValid()` instead. |
| `getValidationTarget()` | Resolves `ControlToValidate` to a `TControl` instance. Throws `TConfigurationException` if the ID is empty or not found. |
| `getValidationValue(TControl $control)` | Returns `$control->getValidationPropertyValue()`. Requires the control to implement `IValidatable`; throws `TInvalidDataTypeException` otherwise. |
| `getClientSide()` | Returns a `TValidatorClientSide` for attaching JavaScript callbacks (`OnValidate`, `OnValidationSuccess`, `OnValidationError`). |
| `getClientScriptOptions()` | Returns the array of options passed to the JavaScript validator constructor. Override in subclasses to add validator-specific options. |
| `getClientClassName()` | **Abstract.** Must return the JavaScript class name (e.g., `'Prado.Validation.TRequiredFieldValidator'`). |
| `evaluateIsValid()` | **Abstract.** Subclasses implement the actual validation logic here. Return `true` if valid. |

## Events

| Event | Raised When |
|---|---|
| `OnValidate` | Before validation runs (only if the validator is visible and enabled) |
| `OnValidationSuccess` | After validation completes and the result is valid |
| `OnValidationError` | After validation completes and the result is invalid |

## Patterns & Gotchas

- **Multiple validators per control** — Any number of validators may target the same input. Each validates independently.
- **Validation timing** — Server-side validation runs after `TPage::onLoad` and before postback events. If you need `TPage::getIsValid()` inside `onLoad`, call `TPage::validate()` first.
- **Disabled validator** — Setting `Enabled=false` immediately sets `IsValid=true` and skips validation.
- **Disabled target control** — If the target `TWebControl` has `Enabled=false`, the validator skips evaluation and reports valid.
- **`ControlCssClass`** — Applied to the target control's `CssClass` when invalid; stripped on success. Only works when the target is a `TWebControl`.
- **`TValidationSummary`** — Reads `ErrorMessage` (not `Text`) from each validator in the same `ValidationGroup`.
- **Subclassing** — Must implement both `evaluateIsValid()` and `getClientClassName()`. Call `parent::getClientScriptOptions()` and merge in subclass-specific keys.
- **Constructor** — Sets `ForeColor` to `'red'` by default.
- **Client-side special controls** — `TCheckBox`, `TDatePicker`, `THtmlArea`, `THtmlArea5`, `TReCaptcha2`, `TCheckBoxList`, `TListBox`, `TRadioButton` receive special handling in `validation3.js`. New controls needing custom JS extraction must be added to both `$_clientClass` and the JS file.
