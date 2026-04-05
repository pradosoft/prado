# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Internationalization and localization for the Prado framework. Covers culture-aware date/number formatting, message translation, plural-choice formatting, and browser-language auto-detection.

## Classes

### Module & Configuration

- **`TGlobalization`** — `TModule` subclass. Register in `application.xml`. Properties: `Culture`, `Charset`, `DefaultCulture`, `DefaultCharset`, `TranslateDefaultCulture`. Access via `Prado::getGlobalization()`. Raises `onAfterInit`.

- **`TGlobalizationAutoDetect`** — Extends `TGlobalization`; auto-sets `Culture` from the HTTP `Accept-Language` header.

### Formatting

- **`TDateFormat`** — Localized date/time formatting and parsing. Methods: `format($date, $pattern, $culture)`, `parse($string, $pattern, $culture)`. Supports ICU-style pattern strings.

- **`TNumberFormat`** — Localized number formatting and parsing. Handles currency, percentage, decimal patterns.

- **`TChoiceFormat`** — Plural/choice selection. Maps a numeric value to a localized string (e.g., `"0#no items|1#one item|2+#many items"`). Handles complex plural forms (Russian, Arabic, etc.).

- **`TSimpleDateFormatter`** (in `Util/`) — Non-locale-aware date formatting for simple cases.

### Template Controls

- **`TTranslate`** — Template control (`<com:TTranslate>`). Renders translated text. Supports named parameter substitution via `<com:TTranslateParameter>`.

- **`TTranslateParameter`** — Named parameter `<Key>=<Value>` for use inside `<com:TTranslate>`.

- **`TI18NControl`** — Base for I18N-aware PRADO controls.

- **`Translation`** — Static helper for programmatic translation access outside templates.

## Subdirectories

### `core/` — Message Sources & Translation Engine

- **`IMessageSource`** — Interface: `load()`, `read()`, `save()`, `append()`, `delete()`, `update()`, `catalogues()`.
- **`MessageSource`** — Abstract base with factory method `MessageSource::factory($type, $source)`. Valid types: `'XLIFF'`, `'PHP'`, `'gettext'`, `'Database'`. Always use the factory — never instantiate backends directly.
- **`MessageSource_XLIFF`** — XLIFF (XML Localization Interchange File Format) backend.
- **`MessageSource_gettext`** — GNU gettext (`.po` / `.mo` files) backend.
- **`MessageSource_PHP`** — PHP array-based translation files.
- **`MessageSource_Database`** — Database-backed translations.
- **`MessageFormat`** — Looks up and formats translated strings. Supports `{name}` token substitution.
- **`ChoiceFormat`** — Evaluates ICU-style plural/interval expressions (e.g., `"0#none|1#one item|1<{0} items"`). Used by `MessageFormat` for pluralisation.
- **`MessageCache`** — Filesystem cache for compiled catalogues. Invalidates when the source file `mtime` changes.
- **`TCache_Lite`** — Lightweight file-based cache used internally by `MessageCache`; low-dependency alternative to the full Prado cache stack.
- **`CultureInfo`** — ICU-based culture data for 100+ locales: language names, country codes, date/time patterns, number formats, calendar types. Uses `TNumberFormatterTrait` for `formatNumber()`, `formatCurrency()`, `formatUnit()`.
- **`CultureInfoUnits`** — ICU unit bundle data for `CultureInfo::getUnit()` / `formatUnit()` / `formatPerUnit()`.
- **`TNumberFormatterTrait`** — Shared trait for number/currency/percentage formatting via ICU pattern strings.
- **`TMessageSourceIOException`** — Exception thrown on catalogue read/write failures.

#### `core/Gettext/` — GNU Gettext File I/O

- **`TGettext`** — Abstract base for `.po`/`.mo` file handling.
- **`TGettext_MO`** — Reads/writes compiled binary `.mo` files; handles both endiannesses. Used at runtime.
- **`TGettext_PO`** — Reads/writes source text `.po` files including plural forms and metadata headers. Used for translation authoring.

### `schema/` — Database Schema

SQL schema files for creating translation tables in different DBMS (used with `MessageSource_Database`).

## Conventions

- **Culture format:** RFC 4646 tags — `en_US`, `zh_CN`, `pt_BR`.
- **Message catalogues:** Organize by domain (e.g., `'messages'`, `'app.users'`). Configured on the message source in `application.xml`.
- **Parameter substitution:** `{0}`, `{1}`, … positional placeholders in message strings.
- **Multiple sources with fallback:** Chain message sources; untranslated strings fall through to the next source then to the default language.
- **`TranslateDefaultCulture = false`** — Skip translation when the current culture equals the default (avoids a lookup round-trip).

## Gotchas

- `TGlobalization` must be initialized before any translation is attempted (module init order matters).
- Culture string must exactly match an ICU locale tag; invalid tags silently fall back to the default.
- `TChoiceFormat` plural expressions vary significantly by language — test with multiple values.
