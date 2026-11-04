# Web/UI/WebControls/TPasswordStrengthMeter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPasswordStrengthMeter`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPasswordStrengthMeter.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TWebControl](./TWebControl.md)

## Overview
Shows the estimated strength of the password typed in `ControlToMeasure`. Its bar fills from the left and shifts continuously from red to green as the estimated entropy grows. The [TPasswordStrength](./TPasswordStrength.md) level name (`ShowText`) and the bits (`ShowBits`) can follow the bar. The estimate is `TPasswordStrengthValidator::estimateEntropy()`, recomputed in the browser on each `input` event. The meter informs only; [TPasswordStrengthValidator](./TPasswordStrengthValidator.md) enforces a minimum.

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
| `ShowText` | `true` | Show the level name after the bar. |
| `ShowBits` | `false` | Show the estimate in bits after the bar. |
| `BitsFormat` | `{0} bits` | Format of the bits text; `{0}` is the rounded bits. |
| `LevelTexts` | `Very weak,Weak,Medium,Strong,Very strong` | The five level names, comma separated; `getLevelTextList()` fills missing names with the defaults. |
| `CssUrl` | `default` | `default` publishes `assets/passwordstrengthmeter.css`; `''` registers none. |

## Mechanics

| State | Fill width | Hue | Level text | Bits text |
|---|---|---|---|---|
| empty | 0% | 0 | none | none |
| `bits` > 0 | `min(bits / MaxEntropy, 1) × 100%` | `round(min(bits / MaxEntropy, 1) × 120)` | the level of `bits` | `BitsFormat` with `round(bits)` |

- Markup: `<div class="prado-password-meter" role="meter">` holding `<span class="prado-password-meter-bar"><span class="prado-password-meter-fill"></span></span>`, then `<span class="prado-password-meter-text">` with `ShowText` and `<span class="prado-password-meter-bits">` with `ShowBits`. `CssClass` is appended to the meter class.
- `Prado.WebUI.TPasswordStrengthMeter` in `validation3.js` (the `validator` package) sets the fill width and `--prado-meter-hue` on the bar, and fills the texts that are present. It shares `Prado.Validation.estimatePasswordEntropy()` with the validator. The client options are `LevelEntropy` (from `STRENGTH_ENTROPY`), `LevelTexts`, and `BitsFormat`.
- CSS: the fill uses `oklch(0.56 0.19 25…145)` in light and `oklch(0.74 0.17 …)` in dark through `light-dark()`, with HSL fallbacks declared first. One OKLCH lightness keeps every hue at a 3:1 contrast or better against the page. The bar has a `#767676` / `#8f8f8f` border. The width transition (0.15 s) is off under `prefers-reduced-motion: reduce`. Under `forced-colors`, the fill uses `Highlight`.

## Accessibility

- The element is `role="meter"` with `aria-valuemin="0"`, `aria-valuemax` = MaxEntropy, `aria-valuenow` = bits capped at MaxEntropy, and `aria-valuetext` = the level name.
- The default accessible name is `aria-label="Password strength"`; an `aria-label` or `aria-labelledby` attribute replaces it.
- The bar and the texts are `aria-hidden`, so assistive technology reads the meter values once. `aria-valuetext` is the level name whether or not `ShowText` shows it.
- The fill length, and the level name with `ShowText`, keep the strength readable without color (WCAG 1.4.1). The meter is not a live region, so typing is not announced.

## Tests

- `tests/unit/Web/UI/WebControls/TPasswordStrengthMeterTest.php`
- `tests/js/validator/passwordstrengthvalidator.test.js` (meter block)
- `tests/playwright/validators/PasswordStrengthValidatorTestCase.spec.js`: the fill grows, red to green, level names, bits, meter role and values, a 3:1 contrast at each step, and a meter with `ShowText="false"`.

## See Also

- [TPasswordStrengthValidator](./TPasswordStrengthValidator.md)
- [TPasswordStrength](./TPasswordStrength.md)

**@since 4.4.0**
