# I18N/TNumberFormat

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TNumberFormat`**

## Class Info
**Location:** `framework/I18N/TNumberFormat.php`
**Namespace:** `Prado\I18N`

## Overview
Localized number, currency, and percentage formatting control. Uses PHP's `NumberFormatter` internally.

## Usage

```php
<com:TNumberFormat Type="currency" Currency="EUR" Value="1234.56" />

<com:TNumberFormat Type="percent" Value="0.25" />
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Value` | float | The number to format |
| `Pattern` | string | Custom ICU decimal pattern |
| `Type` | string | Formatting type: `'decimal'`, `'currency'`, `'percentage'`, `'scientific'`, `'spellout'`, `'ordinal'`, `'duration'`, `'accounting'` |
| `Currency` | string | 3-letter ISO 4217 currency code (e.g., `'USD'`, `'EUR'`) |
| `DefaultText` | string | Text to display when Value is empty |
| `Culture` | string | Locale (falls back to `TI18NControl` hierarchy) |

## Type Examples

```php
// Currency
<com:TNumberFormat Type="currency" Currency="USD" Value="1234567.89" />
<!-- "$1,234,567.89" -->

// Percentage
<com:TNumberFormat Type="percentage" Value="0.1234" />
<!-- "12%" -->

// Decimal
<com:TNumberFormat Type="decimal" Value="1234567.89" Culture="de_DE" />
<!-- "1.234.567,89" -->
```

## See Also

- [TI18NControl](./TI18NControl.md) - Base class providing Culture/Charset
- [TDateFormat](./TDateFormat.md) - Date formatting control
- [CultureInfo](./core/CultureInfo.md) - ICU locale data for number patterns