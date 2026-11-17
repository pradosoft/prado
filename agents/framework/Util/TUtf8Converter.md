# Util/TUtf8Converter

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TUtf8Converter`**

## Class Info
**Location:** `framework/Util/TUtf8Converter.php`
**Namespace:** `Prado\Util`
**Since:** 4.0.2

## Overview
`TUtf8Converter` is a static utility class that converts strings to and from UTF-8. It calls `iconv()` when the iconv extension is loaded, and the intl extension's `\UConverter::transcode()` otherwise (4.4.0); `composer.json` suggests `iconv` and requires `intl`. An encoding can carry a language after a period, such as `'ISO-8859-1.fr_FR'`; the conversion runs under that `LC_CTYPE` locale, which is restored afterward. A failed conversion returns the original string.

| Case | With iconv | Without iconv |
|---|---|---|
| `//TRANSLIT` or `//IGNORE` on the encoding | applied | removed; ICU substitutes what the target encoding cannot hold |
| unknown encoding | the string unchanged | the string unchanged |
| input invalid in its encoding | the string unchanged | the converted string with substitutes |

## Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `static hasIconv(): bool` | `bool` | Whether the iconv extension is loaded and `iconv()` converts. @since 4.4.0 |
| `static toUTF8(string $string, string $from, ?string $lang = null): string` | `string` | Converts `$string` from encoding `$from` to UTF-8. Returns the original string if the conversion fails. |
| `static fromUTF8(string $string, string $to, ?string $lang = null): string` | `string` | Converts a UTF-8 `$string` to encoding `$to`. Returns the original string if the conversion fails. |
| `static parseEncodingLanguage(string &$encoding, mixed &$lang): void` | `void` | Splits `'ISO-8859-1.fr_FR'` into the encoding `'ISO-8859-1'` and the language `'fr_FR'`, written back through the reference parameters. |

## See Also

- [`TGlobalization`](../I18N/TGlobalization.md) — framework globalization module that coordinates character-set handling
