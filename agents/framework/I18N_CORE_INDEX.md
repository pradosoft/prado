# I18N/core/INDEX.md - I18N_CORE_INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Subdirectories

| Directory | Purpose |
|---|---|
| [`Gettext/`](I18N_CORE_GETTEXT_INDEX.md)] | GNU Gettext `.po`/`.mo` file reader/writer |

## Purpose

Core I18N engine: culture information, message source backends, message formatting, plural/choice rules, number formatting, and a lightweight cache. Used internally by `framework/I18N/` (TGlobalization, TTranslate, etc.).

## Classes

### Culture & Locale

- **`CultureInfo`** — Represents a specific locale (e.g., `en_AU`, `zh_CN`). Uses `TNumberFormatterTrait` for number/currency/unit formatting. Key methods: `getCurrency()`, `getDateTimeFormat()`, `getNumberFormat()`, `getUnit($type, $unit)`, `formatUnit($value, $unit)`. Locale names follow ISO 639 + ISO 3166 (`language_REGION`).

- **`CultureInfoUnits`** — Data class holding ICU unit bundle data used by `CultureInfo::getUnit()` / `formatUnit()` / `formatPerUnit()`.

- **`TNumberFormatterTrait`** — Shared trait providing `formatNumber()`, `formatCurrency()`, `formatPercentage()` using ICU pattern strings. Used by `CultureInfo` and `TNumberFormat` (parent directory).

### Message Sources

- **`IMessageSource`** — Interface for translation backends. Methods: `load($catalogue)`, `translateMessage($catalogue, $message, $params, $locale)`, `setCulture($locale)`, `setCache($cache)`.

- **`MessageSource`** — Abstract base with factory method: `MessageSource::factory($type, $source)`. Valid `$type` values: `'XLIFF'`, `'PHP'`, `'gettext'`, `'Database'`. Handles catalogue loading, caching, and culture fallback.

- **`MessageSource_XLIFF`** — XLIFF (`.xlf`) XML-format message storage. Industry-standard translation file format.

- **`MessageSource_PHP`** — PHP array-based message storage. Each catalogue is a `.php` file returning `['source' => 'translation']`.

- **`MessageSource_gettext`** — GNU Gettext `.po`/`.mo` message storage. Uses `TGettext_MO`/`TGettext_PO` from `Gettext/` subdirectory.

- **`MessageSource_Database`** — Database-backed message storage. Uses a `TDbConnection` to store/retrieve translations from a configurable table.

- **`TMessageSourceIOException`** — Exception thrown on catalogue read/write failures.

### Message Formatting

- **`MessageFormat`** — Looks up translated strings from a `MessageSource` and applies token substitution. Usage:
  ```php
  $source = MessageSource::factory('XLIFF', '/path/to/messages/');
  $source->setCulture('fr_FR');
  $fmt = new MessageFormat($source);
  echo $fmt->format('Hello {name}', ['{name}' => 'Alice']);
  ```

- **`ChoiceFormat`** — Evaluates ICU-style choice expressions for plural/interval rules (e.g., `"0#none|1#one item|1<{0} items"`). Used by `MessageFormat` for pluralisation.

### Caching

- **`MessageCache`** — Filesystem cache for compiled message catalogues. Stores serialised translation arrays in a cache directory. Invalidates when the source file `mtime` changes.

- **`TCache_Lite`** — Lightweight file-based cache (legacy). Used internally by `MessageCache` as a low-dependency alternative to the full Prado cache stack.

## Subdirectory: `Gettext/`

`TGettext`, `TGettext_MO`, `TGettext_PO` — GNU Gettext `.po`/`.mo` file reader/writer. See `Gettext/CLAUDE.md`.

## Patterns & Gotchas

- **Always use `MessageSource::factory()`** to create a message source — never instantiate backends directly.
- **Culture fallback** — if a translation is not found for `zh_CN`, the source falls back to `zh`, then to the default catalogue.
- **`MessageCache` invalidation** — cache is based on source file `mtime`; changes to translation files are picked up automatically on the next request.
- **`ChoiceFormat` expression syntax** — uses `|` as separator; numeric ranges use `#` (equals) or `<` (greater than) prefixes.
- **`TCache_Lite`** writes to a temp directory; ensure that directory is writable and not shared across application instances with different locale data.
