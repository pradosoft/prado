# I18N/TDateFormat

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TDateFormat`**

## Class Info
**Location:** `framework/I18N/TDateFormat.php`
**Namespace:** `Prado\I18N`

## Overview
Localized date/time formatting control. Uses PHP's `IntlDateFormatter` internally with ICU-style pattern strings.

## Usage

```php
<com:TDateFormat Pattern="yyyy-MM-dd" Value="2024-01-15" />

<com:TDateFormat Culture="de_DE" Value="2024-01-15" Pattern="full" />
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Value` | mixed | Date/time to format (timestamp, date string, or empty for current time) |
| `Pattern` | string | ICU date pattern (e.g., `'yyyy-MM-dd'`) or preset (`'full'`, `'long'`, `'medium'`, `'short'`) |
| `DefaultText` | string | Text to display when Value is empty |
| `Culture` | string | Locale (falls back to `TI18NControl` hierarchy) |
| `Charset` | string | Output charset (falls back to `TI18NControl` hierarchy) |

## Pattern Presets

Two presets can be combined for date and time separately:

```php
<com:TDateFormat Pattern="medium long" Value="2024-01-15 15:30:00" />
<!-- "Jan 15, 2024 at 3:30:00 PM" -->

<com:TDateFormat Pattern="full" Value="2024-01-15" />
<!-- "Thursday, January 15, 2024" -->
```

## ICU Pattern Tokens

| Token | Description | Example |
|-------|-------------|---------|
| `yyyy` | 4-digit year | 2024 |
| `MM` | Month (01-12) | 01 |
| `dd` | Day (01-31) | 15 |
| `HH` | Hour (00-23) | 15 |
| `mm` | Minute (00-59) | 30 |
| `ss` | Second (00-59) | 45 |
| `EEEE` | Day name | Thursday |
| `MMMM` | Month name | January |

## See Also

- [TI18NControl](./TI18NControl.md) - Base class providing Culture/Charset
- [TNumberFormat](./TNumberFormat.md) - Number formatting control
- [CultureInfo](./core/CultureInfo.md) - ICU locale data