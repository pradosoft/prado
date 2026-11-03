# Web/UI/WebControls/TPasswordStrengthMeter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPasswordStrengthMeter`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPasswordStrengthMeter.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TWebControl](./TWebControl.md)

## Overview
Shows the estimated strength of the password typed in `ControlToMeasure`. It renders a row of notches that fill from the left and shift from red to green as the estimated entropy grows, followed by the [TPasswordStrength](./TPasswordStrength.md) level name. The estimate is `TPasswordStrengthValidator::estimateEntropy()`, recomputed in the browser on each `input` event. The meter informs only; [TPasswordStrengthValidator](./TPasswordStrengthValidator.md) enforces a minimum.

```xml
<com:TTextBox ID="Password" TextMode="Password" Attributes.autocomplete="new-password" />
<com:TPasswordStrengthMeter ControlToMeasure="Password" />
<com:TPasswordStrengthValidator ControlToValidate="Password" />
```

## Key Properties

| Property | Default | Description |
|---|---|---|
| `ControlToMeasure` | `''` | ID path of the password control; `getMeasureTarget()` throws `passwordstrengthmeter_controltomeasure_invalid` when it is empty or unknown. |
| `MaxEntropy` | `120` | Bits that fill the bar and reach green; at least 1. |
| `Notches` | `20` | Number of notches, 2 to 100. More notches show finer steps. |
| `LevelTexts` | `Very weak,Weak,Medium,Strong,Very strong` | The five level names, comma separated; `getLevelTextList()` fills missing names with the defaults. |
| `CssUrl` | `default` | `default` publishes `assets/passwordstrengthmeter.css`; `''` registers none. |

## Mechanics

| State | Notches lit | Hue | Text |
|---|---|---|---|
| empty | 0 | 0 | none |
| `bits` > 0 | `max(1, ceil(min(bits / MaxEntropy, 1) × Notches))` | `round(min(bits / MaxEntropy, 1) × 120)` | the level of `bits` |

- Markup: `<div class="prado-password-meter" role="meter">` holding `<span class="prado-password-meter-bar">` with the notch spans, then `<span class="prado-password-meter-text">`. `CssClass` is appended to the meter class.
- `Prado.WebUI.TPasswordStrengthMeter` in `validation3.js` (the `validator` package) toggles `lit` on the notches and sets `--prado-meter-hue` on the bar. It shares `Prado.Validation.estimatePasswordEntropy()` with the validator. The client options are `LevelEntropy` (from `STRENGTH_ENTROPY`) and `LevelTexts`.
- CSS: lit notches use `oklch(0.56 0.19 25…145)` in light and `oklch(0.74 0.17 …)` in dark through `light-dark()`, with HSL fallbacks declared first. One OKLCH lightness keeps every hue at a 3:1 contrast or better against the page. Empty notches have a `#767676` / `#8f8f8f` border. Under `forced-colors`, lit notches use `Highlight`.

## Accessibility

- The element is `role="meter"` with `aria-valuemin="0"`, `aria-valuemax` = MaxEntropy, `aria-valuenow` = bits capped at MaxEntropy, and `aria-valuetext` = the level name.
- The default accessible name is `aria-label="Password strength"`; an `aria-label` or `aria-labelledby` attribute replaces it.
- The bar and the text are `aria-hidden`, so assistive technology reads the meter values once.
- The level name keeps the strength readable without color (WCAG 1.4.1). The meter is not a live region, so typing is not announced.

## Tests

- `tests/unit/Web/UI/WebControls/TPasswordStrengthMeterTest.php`
- `tests/js/validator/passwordstrengthvalidator.test.js` (meter block)
- `tests/playwright/validators/PasswordStrengthValidatorTestCase.spec.js`: notches fill, red to green, level names, meter role and values, and a 3:1 contrast at each step.

## See Also

- [TPasswordStrengthValidator](./TPasswordStrengthValidator.md)
- [TPasswordStrength](./TPasswordStrength.md)

**@since 4.4.0**
