# Util/TSimpleDateFormatter

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TSimpleDateFormatter`**

## Class Info
**Location:** `framework/Util/TSimpleDateFormatter.php`
**Namespace:** `Prado\Util`
**Uses:** `TIntlDateFormatterTrait`
**Since:** 3.0

## Overview
`TSimpleDateFormatter` formats and parses date strings according to a pattern string. When a culture is set it delegates to `IntlDateFormatter` for locale-aware month and weekday names; otherwise it falls back to PHP's `DateTime`. It is the date-formatting engine used by Prado's date-picker and validation controls.

## Constructor

`__construct(string $pattern, string $charset = 'UTF-8', ?string $culture = null)`

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Pattern` | `string` | — | The format pattern (e.g. `'MM/dd/yyyy'`). |
| `Charset` | `string` | `'UTF-8'` | Character set used when measuring and slicing strings. |
| `Culture` | `string` | `''` | Locale code (e.g. `'fr_FR'`) for culture-specific month and weekday names. |

## Key Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `format(mixed $value): string` | `string` | Formats a timestamp or date string according to `Pattern`. |
| `parse(mixed $value, bool $defaultToCurrentTime = true): ?int` | `?int` | Parses a date string and returns a Unix timestamp, or `null` when parsing fails and `$defaultToCurrentTime` is `false`. |
| `parseExact(mixed $value, bool $defaultToCurrentTime = true): ?float` | `?float` | Like `parse()` but returns a float timestamp with sub-second precision. |
| `isValidDate(mixed $value): bool` | `bool` | Returns `true` when `$value` matches the pattern and represents a valid calendar date. |
| `getMonthPattern(): string\|false` | `string\|false` | Extracts the month token (`M`, `MM`, `MMM`, or `MMMM`) from `Pattern`. |
| `getDayPattern(): string\|false` | `string\|false` | Extracts the day token (`d` or `dd`) from `Pattern`. |
| `getYearPattern(): string\|false` | `string\|false` | Extracts the year token (`yy` or `yyyy`) from `Pattern`. |
| `getDayMonthYearOrdering(): array` | `array` | Returns the ordered list of `'day'`, `'month'`, `'year'` tokens as they appear in `Pattern`. |

## See Also

- `TIntlDateFormatterTrait` — provides ICU integration
- [`TDatePicker`](../Web/UI/WebControls/TDatePicker.md) — a consumer of this formatter
