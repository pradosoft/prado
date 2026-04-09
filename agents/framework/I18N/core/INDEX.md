# I18N/core/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[./](../INDEX.md) > [I18N](../INDEX.md) > [core](./INDEX.md)

| Directory | Purpose |
|---|---|
| [Gettext/](Gettext/INDEX.md) | GNU Gettext `.po`/`.mo` file reader/writer |

## Purpose

Core I18N engine: culture information, message source backends, message formatting, plural/choice rules, number formatting, and a lightweight cache. Used internally by `framework/I18N/` ([`TGlobalization`](../TGlobalization.md), [`TTranslate`](../TTranslate.md), etc.).

## Classes

### Culture & Locale

- **[`CultureInfo`](CultureInfo.md)** — Represents a specific locale (e.g., `en_AU`, `zh_CN`). Uses [`TNumberFormatterTrait`](TNumberFormatterTrait.md) for number/currency/unit formatting. Key methods: `getCurrency()`, `getDateTimeFormat()`, `getNumberFormat()`, `getUnit($type, $unit)`, `formatUnit($value, $unit)`. Locale names follow ISO 639 + ISO 3166 (`language_REGION`).

- **[`CultureInfoUnits`](CultureInfoUnits.md)** — Data class holding ICU unit bundle data used by [`CultureInfo::getUnit()`](CultureInfo.md) / `formatUnit()` / `formatPerUnit()`.

- **[`TNumberFormatterTrait`](TNumberFormatterTrait.md)** — Shared trait providing `formatNumber()`, `formatCurrency()`, `formatPercentage()` using ICU pattern strings. Used by [`CultureInfo`](CultureInfo.md) and [`TNumberFormat`](../TNumberFormat.md) (parent directory).

### Message Sources

- **[`IMessageSource`](IMessageSource.md)** — Interface for translation backends. Methods: `load($catalogue)`, `translateMessage($catalogue, $message, $params, $locale)`, `setCulture($locale)`, `setCache($cache)`.

- **[`MessageSource`](MessageSource.md)** — Abstract base with factory method: `MessageSource::factory($type, $source)`. Valid `$type` values: `'XLIFF'`, `'PHP'`, `'gettext'`, `'Database'`. Handles catalogue loading, caching, and culture fallback.

- **[`MessageSource_XLIFF`](MessageSource_XLIFF.md)** — XLIFF (`.xlf`) XML-format message storage. Industry-standard translation file format.

- **[`MessageSource_PHP`](MessageSource_PHP.md)** — PHP array-based message storage. Each catalogue is a `.php` file returning `['source' => 'translation']`.

- **[`MessageSource_gettext`](MessageSource_gettext.md)** — GNU Gettext `.po`/`.mo` message storage. Uses [`TGettext_MO`](../Gettext/TGettext_MO.md)/[`TGettext_PO`](../Gettext/TGettext_PO.md) from `Gettext/` subdirectory.

- **[`MessageSource_Database`](MessageSource_Database.md)** — Database-backed message storage. Uses a [`TDbConnection`](../TDbConnection.md) to store/retrieve translations from a configurable table.

- **[`TMessageSourceIOException`](TMessageSourceIOException.md)** — Exception thrown on catalogue read/write failures.

### Message Formatting

- **[`MessageFormat`](MessageFormat.md)** — Looks up translated strings from a [`MessageSource`](MessageSource.md) and applies token substitution. Usage:
  ```php
  $source = MessageSource::factory('XLIFF', '/path/to/messages/');
  $source->setCulture('fr_FR');
  $fmt = new MessageFormat($source);
  echo $fmt->format('Hello {name}', ['{name}' => 'Alice']);
  ```

- **[`ChoiceFormat`](ChoiceFormat.md)** — Evaluates ICU-style choice expressions for plural/interval rules (e.g., `"0#none|1#one item|1<{0} items"`). Used by [`MessageFormat`](MessageFormat.md) for pluralisation.

### Caching

- **[`MessageCache`](MessageCache.md)** — Filesystem cache for compiled message catalogues. Stores serialised translation arrays in a cache directory. Invalidates when the source file `mtime` changes.

- **[`TCache_Lite`](TCache_Lite.md)** — Lightweight file-based cache (legacy). Used internally by [`MessageCache`](MessageCache.md) as a low-dependency alternative to the full Prado cache stack.

## Subdirectory: `Gettext/`

[`TGettext`](Gettext/TGettext.md), [`TGettext_MO`](Gettext/TGettext_MO.md), [`TGettext_PO`](Gettext/TGettext_PO.md) — GNU Gettext `.po`/`.mo` file reader/writer.

## Patterns & Gotchas

- **Always use [`MessageSource::factory()`](MessageSource.md)** to create a message source — never instantiate backends directly.
- **Culture fallback** — if a translation is not found for `zh_CN`, the source falls back to `zh`, then to the default catalogue.
- **`MessageCache` invalidation** — cache is based on source file `mtime`; changes to translation files are picked up automatically on the next request.
- **`ChoiceFormat` expression syntax** — uses `|` as separator; numeric ranges use `#` (equals) or `<` (greater than) prefixes.
- **`TCache_Lite`** writes to a temp directory; ensure that directory is writable and not shared across application instances with different locale data.
