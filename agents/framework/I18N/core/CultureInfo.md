# I18N/core/CultureInfo

### Directories
[framework](../../INDEX.md) / [I18N](../INDEX.md) / [core](INDEX.md) / **`CultureInfo`**

**Location:** `framework/I18N/core/CultureInfo.php`
**Namespace:** `Prado\I18N\core`

## Overview
Represents a specific locale/culture (e.g., `en_AU`, `zh_CN`, `fr_FR`). Wraps PHP's `ResourceBundle` (ICU data) to expose localized culture data: language names, country names, currencies, time zones, calendars, number/date formats, and units.

Culture names follow ISO 639 (`language`) + ISO 3166 (`REGION`) format, separated by underscore: `en_AU`, `pt_BR`. Neutral cultures are two-character language codes only (`en`, `fr`).

Uses `TNumberFormatterTrait` to add `formatNumber()` / `formatCurrency()` support via PHP's `NumberFormatter` class. Unit formatting methods (`getUnit()`, `formatUnit()`, `formatPerUnit()`) are new in 4.3.3.

Static ICU data is cached in `self::$data` (shared across all instances, keyed by culture then bundle name) so repeated lookups within a request are cheap.

## Key Constants

| Constant | Value | Description |
|---|---|---|
| `ALL` | `0` | Return all cultures in `getCultures()` |
| `NEUTRAL` | `1` | Return only neutral (2-char) cultures |
| `SPECIFIC` | `2` | Return only specific (region) cultures |

## Key Properties

| Property | Type | Description |
|---|---|---|
| `$data` | `static array` | Shared ICU ResourceBundle cache keyed `[culture][bundleKey]` |
| `$bundleNames` | `static array` | Maps bundle logical names (`Core`, `Currencies`, `Languages`, `Countries`, `zoneStrings`, `Units`) to ICU bundle IDs |
| `$culture` | `string` | Current locale string |
| `$properties` | `array` | Class methods list used by `__get`/`__set` magic |

## Key Methods

### Construction & Configuration

- `__construct($culture = 'en')` — Accepts a culture string; validates format with `/^[_\w]+$/`; empty string defaults to `'en'`.
- `static getCultureInfo($culture = null)` — Returns a cached `CultureInfo` instance; falls back to application globalization culture when `$culture` is null.
- `static getInvariantCulture()` — Singleton returning a `CultureInfo('en')` instance. Shared — changes affect all callers.
- `static validCulture($culture)` — Checks if culture exists in `getCultures()`, trying direct match, POSIX→BCP 47, and BCP 47→POSIX. **Expensive** (calls `getCultures()` internally). @since 4.3.3

### Culture Lists

- `static getCultures($type = ALL)` — Returns `ResourceBundle::getLocales('')` filtered by type. **EXPENSIVE** — traverses all ICU locale data. Cache the result; never call in a loop.
  - `ALL` — returns all locales.
  - `NEUTRAL` — returns only 2-char language codes.
  - `SPECIFIC` — returns all codes with region suffixes.

### Data Access

- `findInfo($path, $key = null)` — Low-level ICU data accessor. Path is `/`-separated (e.g., `'calendar/default'`, `'Languages/en'`). Auto-detects bundle from path prefix if `$key` is null. Loads the bundle lazily and caches in `self::$data`.
- `getCalendar()` — Returns default calendar name (e.g., `'gregorian'`).
- `getNativeName()` — Culture name in its own language, e.g., `'Deutsch (Deutschland)'`.
- `getEnglishName()` — Culture name in English using invariant culture data.
- `getIsNeutralCulture()` — `true` if culture string is exactly 2 characters.
- `getCountries()` — Localized country name array.
- `getCurrencies()` — Localized currency array (static cache per instance).
- `getLanguages()` — Localized language name array.
- `getScripts()` — Localized script name array.
- `getTimeZones()` — Filtered list of standard timezone identifiers (filters to valid geographic prefixes).

### Number & Unit Formatting (since 4.3.3)

- `formatNumber($number, $format = null)` — Formats using `NumberFormatter::DECIMAL` by default. Falls back to `number_format()` if `NumberFormatter` unavailable.
- `getUnits()` — Full ICU units bundle array.
- `getUnit($unitType)` — Display name for a unit type string like `'digital-gigabyte'`. Hyphens are converted to `/` for ICU path traversal.
- `formatUnit($number, $unitType)` — Returns localized `"{0} meters"` / `"{0} meter"` using plural form based on `$number == 1`.
- `formatPerUnit($number, $unitType)` — Returns localized per-unit pattern (e.g., `"{0} per meter"`).

## POSIX/BCP 47 Duality

PRADO stores culture strings in POSIX underscore form (`en_AU`, `zh_TW`). ICU's `ResourceBundle::getLocales('')` may return entries in either BCP 47 hyphen form (`en-AU`) or POSIX underscore form depending on ICU version. `validCulture()` and `TGlobalizationAutoDetect::getIsValidLocale()` both bridge this by trying all three match directions. When passing PRADO culture strings to ICU lookup functions (`ResourceBundle::create()`, `IntlDateFormatter`, `NumberFormatter`) no conversion is needed — ICU accepts POSIX underscores natively.

## Patterns & Gotchas

- **`getCultures()` is expensive** — it calls `ResourceBundle::getLocales('')` which reads ICU filesystem data. Never call it on every request; cache results at the application level.
- **`validCulture()` calls `getCultures()`** — therefore also expensive. Avoid in hot paths.
- **`getCultureInfo()` caches instances** — prefer this static factory over `new CultureInfo()` in hot paths.
- **Static `$data` cache** — shared across all `CultureInfo` instances for the same culture. Very efficient after the first call per bundle per culture. But this also means all instances of the same culture share their bundle data.
- **Invariant culture is a singleton** — `getInvariantCulture()` uses a `static` variable. Any modification to the returned instance affects all callers using the invariant culture.
- **`__get` / `__set`** — delegates to `getXxx()` / `setXxx()` methods listed in `$this->properties` (populated from `get_class_methods()` in constructor). Only getter methods are accessible as properties; unknown properties throw a plain `Exception` (not a Prado exception).
- **Unit type format** — unit types use hyphen form (`'digital-gigabyte'`, `'length-meter'`); the methods internally convert to slash-separated ICU paths.
- **[TNumberFormatterTrait](./TNumberFormatterTrait.md)** — the `getFormatter()` method from the trait provides a cached `NumberFormatter` instance keyed by culture and format.
- **PHP `intl` extension required** — `ResourceBundle` and `NumberFormatter` require the `intl` PHP extension. The `formatNumber()` fallback only applies when `NumberFormatter` is absent; `findInfo()` will fail silently if `ResourceBundle` is unavailable.
