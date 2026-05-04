# I18N/core/TNumberFormatterTrait

### Directories
[framework](../../INDEX.md) / [I18N](../INDEX.md) / [core](./INDEX.md) / **`TNumberFormatterTrait`**

## Class Info
**Location:** `framework/I18N/core/TNumberFormatterTrait.php`
**Namespace:** `Prado\I18N\core`

## Overview
PHP trait providing `\NumberFormatter` caching for number/currency/percentage formatting. Used by `CultureInfo` and `TNumberFormat`.

## Trait Methods

### `getFormatter($culture, $format): ?\NumberFormatter`

Get or create a cached `NumberFormatter` instance.

```php
protected function getFormatter(string $culture, int $format): ?\NumberFormatter
```

| Format | Value |
|--------|-------|
| `\NumberFormatter::DECIMAL` | Decimal |
| `\NumberFormatter::CURRENCY` | Currency |
| `\NumberFormatter::PERCENT` | Percentage |
| `\NumberFormatter::SCIENTIFIC` | Scientific |
| `\NumberFormatter::SPELLOUT` | Spellout |
| `\NumberFormatter::ORDINAL` | Ordinal |
| `\NumberFormatter::DURATION` | Duration |
| `\NumberFormatter::CURRENCY_ACCOUNTING` | Accounting |

## Caching

Formatters are cached per culture + format combination:
```php
protected static array $formatters = [];
```

## Usage

Classes using this trait:
- `CultureInfo` - Locale data and formatting
- `TNumberFormat` - Number formatting control

## See Also

- [CultureInfo](./CultureInfo.md) - ICU locale data
- [TNumberFormat](../TNumberFormat.md) - Template control using this trait