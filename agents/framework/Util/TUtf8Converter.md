# Util/TUtf8Converter

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TUtf8Converter`**

## Class Info
**Location:** `framework/Util/TUtf8Converter.php`
**Namespace:** `Prado\Util`
**Since:** 4.0.2

## Overview
`TUtf8Converter` is a static utility class that wraps PHP's `iconv` extension to convert strings to and from UTF-8. It also parses encoding strings that embed a language tag (e.g. `'ISO-8859-1//TRANSLIT//FR'`), separating the base encoding from the language hint for `iconv`. Conversion failures return the original string rather than raising an exception.

## Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `static toUTF8(string $string, string $from, ?string $lang = null): string` | `string` | Converts `$string` from encoding `$from` to UTF-8 via `iconv`. Returns the original string if `iconv` fails. |
| `static fromUTF8(string $string, string $to, ?string $lang = null): string` | `string` | Converts a UTF-8 `$string` to encoding `$to` via `iconv`. Returns the original string if `iconv` fails. |
| `static parseEncodingLanguage(string &$encoding, mixed &$lang): void` | `void` | Parses a compound encoding string, splitting the character-set name from an embedded language tag and writing each back through the reference parameters. |

## See Also

- [`TGlobalization`](../I18N/TGlobalization.md) — framework globalization module that coordinates character-set handling
