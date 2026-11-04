# Web/UI/WebControls/TPasswordStrength

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPasswordStrength`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPasswordStrength.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TEnumerable`

## Overview
The password strength levels of [TPasswordStrengthValidator](./TPasswordStrengthValidator.md) and [TPasswordStrengthMeter](./TPasswordStrengthMeter.md), from weakest to strongest. Each level starts at an estimated entropy in bits; `TPasswordStrengthValidator::STRENGTH_ENTROPY` holds the thresholds.

| Value | Bits from |
|---|---|
| `VeryWeak` | 0 |
| `Weak` | 40 |
| `Medium` | 60 |
| `Strong` | 80 |
| `VeryStrong` | 100 |

The thresholds live on the validator because `TPropertyValue::ensureEnum()` would accept any constant of the enum class as a value.

**@since 4.4.0**
