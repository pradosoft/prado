# I18N/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories
[framework](./INDEX.md) / **`I18N/INDEX.md`**

| Directory | Purpose |
|---|---|
| [core/](core/INDEX.md) | Core I18N engine: culture information, message source backends, message formatting, plural/choice rules, number formatting, and a lightweight cache. |
| [core/Gettext/](core/Gettext/INDEX.md) | GNU Gettext `.po`/`.mo` file reader/writer |

## Purpose

Internationalization and localization for the Prado framework. Covers culture-aware date/number formatting, message translation, plural-choice formatting, and browser-language auto-detection.

## Classes

### Module & Configuration

- **[`TGlobalization`](TGlobalization.md)** — [`TModule`](../TModule.md) subclass. Register in `application.xml`. Properties: `Culture`, `Charset`, `DefaultCulture`, `DefaultCharset`, `TranslateDefaultCulture`. Access via `Prado::getGlobalization()`. Raises `onAfterInit`.

- **[`TGlobalizationAutoDetect`](TGlobalizationAutoDetect.md)** — Extends [`TGlobalization`](TGlobalization.md); auto-sets `Culture` from the HTTP `Accept-Language` header.

### Formatting

- **[`TDateFormat`](TDateFormat.md)** — Localized date/time formatting and parsing. Methods: `format($date, $pattern, $culture)`, `parse($string, $pattern, $culture)`. Supports ICU-style pattern strings.

- **[`TNumberFormat`](TNumberFormat.md)** — Localized number formatting and parsing. Handles currency, percentage, decimal patterns.

- **[`TChoiceFormat`](TChoiceFormat.md)** — Plural/choice selection. Maps a numeric value to a localized string (e.g., `"0#no items|1#one item|2+#many items"`). Handles complex plural forms (Russian, Arabic, etc.).

- **`TSimpleDateFormatter`** (in [`Util/`](../Util/INDEX.md)) — Non-locale-aware date formatting for simple cases.

### Template Controls

- **[`TTranslate`](TTranslate.md)** — Template control (`<com:TTranslate>`). Renders translated text. Supports named parameter substitution via `<com:TTranslateParameter>`.

- **[`TTranslateParameter`](TTranslateParameter.md)** — Named parameter `<Key>=<Value>` for use inside `<com:TTranslate>`.

- **[`TI18NControl`](TI18NControl.md)** — Base for I18N-aware PRADO controls.

- **[`Translation`](Translation.md)** — Static helper for programmatic translation access outside templates.

## Subdirectories

### `core/` — Message Sources & Translation Engine

- **[`IMessageSource`](core/IMessageSource.md)** — Interface: `load()`, `read()`, `save()`, `append()`, `delete()`, `update()`, `catalogues()`.
- **[`MessageSource`](core/MessageSource.md)** — Abstract base with factory method `MessageSource::factory($type, $source)`. Valid types: `'XLIFF'`, `'PHP'`, `'gettext'`, `'Database'`. Always use the factory — never instantiate backends directly.
- **[`MessageSource_XLIFF`](core/MessageSource_XLIFF.md)** — XLIFF (XML Localization Interchange File Format) backend.
- **[`MessageSource_gettext`](core/MessageSource_gettext.md)** — GNU gettext (`.po` / `.mo` files) backend.
- **[`MessageSource_PHP`](core/MessageSource_PHP.md)** — PHP array-based translation files.
- **[`MessageSource_Database`](core/MessageSource_Database.md)** — Database-backed translations.
- **[`MessageFormat`](core/MessageFormat.md)** — Looks up and formats translated strings. Supports `{name}` token substitution.
- **[`ChoiceFormat`](core/ChoiceFormat.md)** — Evaluates ICU-style plural/interval expressions (e.g., `"0#none|1#one item|1<{0} items"`). Used by [`MessageFormat`](core/MessageFormat.md) for pluralisation.
- **[`MessageCache`](core/MessageCache.md)** — Filesystem cache for compiled catalogues. Invalidates when the source file `mtime` changes.
- **[`TCache_Lite`](core/TCache_Lite.md)** — Lightweight file-based cache used internally by [`MessageCache`](core/MessageCache.md); low-dependency alternative to the full Prado cache stack.
- **[`CultureInfo`](core/CultureInfo.md)** — ICU-based culture data for 100+ locales: language names, country codes, date/time patterns, number formats, calendar types. Uses [`TNumberFormatterTrait`](core/TNumberFormatterTrait.md) for `formatNumber()`, `formatCurrency()`, `formatUnit()`.
- **[`CultureInfoUnits`](core/CultureInfoUnits.md)** — ICU unit bundle data for [`CultureInfo::getUnit()`](core/CultureInfo.md) / `formatUnit()` / `formatPerUnit()`.
- **[`TNumberFormatterTrait`](core/TNumberFormatterTrait.md)** — Shared trait for number/currency/percentage formatting via ICU pattern strings.
- **[`TMessageSourceIOException`](core/TMessageSourceIOException.md)** — Exception thrown on catalogue read/write failures.

#### `core/Gettext/` — GNU Gettext File I/O

- **[`TGettext`](core/Gettext/TGettext.md)** — Abstract base for `.po`/`.mo` file handling.
- **[`TGettext_MO`](core/Gettext/TGettext_MO.md)** — Reads/writes compiled binary `.mo` files; handles both endiannesses. Used at runtime.
- **[`TGettext_PO`](core/Gettext/TGettext_PO.md)** — Reads/writes source text `.po` files including plural forms and metadata headers. Used for translation authoring.

### `schema/` — Database Schema

SQL schema files for creating translation tables in different DBMS (used with [`MessageSource_Database`](core/MessageSource_Database.md)).

## Conventions

- **Culture format:** RFC 4646 tags — `en_US`, `zh_CN`, `pt_BR`.
- **Message catalogues:** Organize by domain (e.g., `'messages'`, `'app.users'`). Configured on the message source in `application.xml`.
- **Parameter substitution:** `{0}`, `{1}`, … positional placeholders in message strings.
- **Multiple sources with fallback:** Chain message sources; untranslated strings fall through to the next source then to the default language.
- **`TranslateDefaultCulture = false`** — Skip translation when the current culture equals the default (avoids a lookup round-trip).

## Gotchas

- [`TGlobalization`](TGlobalization.md) must be initialized before any translation is attempted (module init order matters).
- Culture string must exactly match an ICU locale tag; invalid tags silently fall back to the default.
- [`TChoiceFormat`](TChoiceFormat.md) plural expressions vary significantly by language — test with multiple values.
