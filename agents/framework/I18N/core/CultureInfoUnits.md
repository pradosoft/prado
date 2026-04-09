# I18N / core / CultureInfoUnits

### Directories
[./](../INDEX.md) > [I18N](../INDEX.md) > [core](./INDEX.md) > [CultureInfoUnits](./CultureInfoUnits.md)

**Location:** `framework/I18N/core/CultureInfoUnits.php`
**Namespace:** `Prado\I18N\core`

## Overview

Constants for culture-specific unit definitions following Unicode CLDR format. Used by `CultureInfo::getUnit()` and `formatUnit()`.

## Unit Type Constants

### Digital Units

| Constant | Value |
|----------|-------|
| `TYPE_DIGITAL_BIT` | `'digital-bit'` |
| `TYPE_DIGITAL_BYTE` | `'digital-byte'` |
| `TYPE_DIGITAL_KILOBIT` | `'digital-kilobit'` |
| `TYPE_DIGITAL_MEGABIT` | `'digital-megabit'` |
| `TYPE_DIGITAL_GIGABIT` | `'digital-gigabit'` |
| `TYPE_DIGITAL_TERABIT` | `'digital-terabit'` |
| `TYPE_DIGITAL_KILOBYTE` | `'digital-kilobyte'` |
| `TYPE_DIGITAL_MEGABYTE` | `'digital-megabyte'` |
| `TYPE_DIGITAL_GIGABYTE` | `'digital-gigabyte'` |
| `TYPE_DIGITAL_TERABYTE` | `'digital-terabyte'` |

### Duration Units

| Constant | Value |
|----------|-------|
| `TYPE_DURATION_NANOSECOND` | `'duration-nanosecond'` |
| `TYPE_DURATION_MICROSECOND` | `'duration-microsecond'` |
| `TYPE_DURATION_MILLISECOND` | `'duration-millisecond'` |
| `TYPE_DURATION_SECOND` | `'duration-second'` |
| `TYPE_DURATION_MINUTE` | `'duration-minute'` |
| `TYPE_DURATION_HOUR` | `'duration-hour'` |
| `TYPE_DURATION_DAY` | `'duration-day'` |
| `TYPE_DURATION_WEEK` | `'duration-week'` |
| `TYPE_DURATION_MONTH` | `'duration-month'` |
| `TYPE_DURATION_YEAR` | `'duration-year'` |

### Length Units

| Constant | Value |
|----------|-------|
| `TYPE_LENGTH_METER` | `'length-meter'` |
| `TYPE_LENGTH_KILOMETER` | `'length-kilometer'` |
| `TYPE_LENGTH_FOOT` | `'length-foot'` |
| `TYPE_LENGTH_INCH` | `'length-inch'` |

### Mass Units

| Constant | Value |
|----------|-------|
| `TYPE_MASS_GRAM` | `'mass-gram'` |
| `TYPE_MASS_KILOGRAM` | `'mass-kilogram'` |
| `TYPE_MASS_POUND` | `'mass-pound'` |

### Other Units

Volume: `TYPE_VOLUME_LITER`, `TYPE_VOLUME_GALLON`
Speed: `TYPE_SPEED_KM_H`, `TYPE_SPEED_MPH`
Temperature: `TYPE_TEMPERATURE_CELSIUS`, `TYPE_TEMPERATURE_FAHRENHEIT`
And more: pressure, electric, energy, force, graphics, light, torque

## Pattern Constants

| Constant | Value | Usage |
|----------|-------|-------|
| `UNIT_DISPLAY_NAME` | `'dnam'` | Unit display name |
| `UNIT_ONE_PATTERN` | `'one'` | Singular format |
| `UNIT_OTHER_PATTERN` | `'other'` | Plural format |
| `UNIT_PER_UNIT_PATTERN` | `'per'` | "per" pattern |

## See Also

- [CultureInfo](./CultureInfo.md) - Uses these constants for unit formatting