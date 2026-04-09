# SUMMARY.md

Core I18N engine providing culture information, message source backends, message formatting, plural/choice rules, number formatting, and caching.

## Classes

- **`CultureInfo`** — Represents a specific locale (e.g., `en_AU`); provides number/currency/unit formatting via `TNumberFormatterTrait`; methods: `getCurrency()`, `getDateTimeFormat()`, `getNumberFormat()`, `getUnit($type, $unit)`.

- **`CultureInfoUnits`** — Data class holding ICU unit bundle data for `CultureInfo::getUnit()` / `formatUnit()` / `formatPerUnit()`.

- **`TNumberFormatterTrait`** — Shared trait providing `formatNumber()`, `formatCurrency()`, `formatPercentage()` using ICU pattern strings.

- **`IMessageSource`** — Interface for translation backends; methods: `load($catalogue)`, `translateMessage($catalogue, $message, $params, $locale)`, `setCulture($locale)`, `setCache($cache)`.

- **`MessageSource`** — Abstract base with factory method `MessageSource::factory($type, $source)`; valid types: `'XLIFF'`, `'PHP'`, `'gettext'`, `'Database'`.

- **`MessageSource_XLIFF`** — XLIFF (`.xlf`) XML-format message storage.

- **`MessageSource_PHP`** — PHP array-based message storage; each catalogue is a `.php` file returning `['source' => 'translation']`.

- **`MessageSource_gettext`** — GNU Gettext `.po`/`.mo` message storage.

- **`MessageSource_Database`** — Database-backed message storage using `TDbConnection`.

- **`TMessageSourceIOException`** — Exception thrown on catalogue read/write failures.

- **`MessageFormat`** — Looks up translated strings from a `MessageSource` and applies token substitution.

- **`ChoiceFormat`** — Evaluates ICU-style choice expressions for plural/interval rules.

- **`MessageCache`** — Filesystem cache for compiled message catalogues; invalidates when source file `mtime` changes.

- **`TCache_Lite`** — Lightweight file-based cache used internally by `MessageCache`.
