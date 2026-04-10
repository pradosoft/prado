# Util/Helpers/TEscCharsetConverter

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Helpers](./INDEX.md) / **`TEscCharsetConverter`**

## Class Info
**Location:** `framework/Util/Helpers/TEscCharsetConverter.php`
**Namespace:** `Prado\Util\Helpers`

## Overview
ESC charset encoding converter. Translates between ISO/IEC escape character sequences and iconv character encodings.

## Key Methods

| Method | Description |
|--------|-------------|
| `decodeEscapeCharset(string $charset): ?string` | Convert ESC sequence to iconv encoding name |
| `encodeEscapeCharset(string $charset): ?string` | Convert iconv encoding name to ESC sequence |

## Constants

| Constant | Description |
|----------|-------------|
| `ESC_CHAR_ENCODINGS_MAP` | Map of ESC sequences to iconv encoding names |

## See Also

- [ISO/IEC 2022](https://en.wikipedia.org/wiki/ISO/IEC_2022)
- [iconv](https://www.php.net/iconv)
