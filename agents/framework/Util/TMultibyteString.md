# Util/TMultibyteString

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TMultibyteString`**

## Class Info
**Location:** `framework/Util/TMultibyteString.php`
**Namespace:** `Prado\Util`
**Final** static helper, `@since 4.4.0`.

## Overview

Measures, cuts, and case-maps UTF-8 strings by character. `composer.json` suggests `mbstring` and requires `pcre` and `intl`, so each method calls the mbstring function when the extension is loaded and otherwise reaches the same result through the required extensions.

| Method | With mbstring | Without mbstring |
|---|---|---|
| `length(string): int` | `mb_strlen()` | the code points `preg_match_all('/./su')` matches |
| `substr(string, int $start, ?int $length): string` | `mb_substr()` | `preg_split('//u')` into code points, then `array_slice()` |
| `upper(string): string` | `mb_strtoupper()` | the ICU `Any-Upper` transliterator |
| `lower(string): string` | `mb_strtolower()` | the ICU `Any-Lower` transliterator |
| `hasMbstring(): bool` | the resolved flag | |

Both paths apply the full Unicode case mapping (`upper('ß')` is `SS`, `lower('İ')` is `i̇`). A string that is not valid UTF-8 is measured and cut in bytes and cased with `strtoupper()` and `strtolower()`. The transliterators are created once per rule and kept in a static.

## Callers

| Caller | Use |
|---|---|
| [`TKeyShortcuts`](../Web/UI/WebControls/TKeyShortcuts.md) `normalizeKey()` | one-character key tokens and their uppercase |
| [`TBaseTerm`](../Web/UI/WebControls/TBaseTerm.md) `getTermKey()` | case-insensitive term keys |
| [`TWebControl`](../Web/UI/WebControls/TWebControl.md) `setAccessKey()` | the one-character check |
| [`TRangeValidator`](../Web/UI/WebControls/TRangeValidator.md) `isValidStringLength()` | `StringLength` when `Charset` is UTF-8; other charsets use `iconv_strlen()` |
| [`TSimpleDateFormatter`](TSimpleDateFormatter.md) `length()`, `substring()` | pattern and value slicing when `Charset` is UTF-8; other charsets use iconv |

## Testing the fallback

`_mbstring` is a private static `?bool` resolved on first use. Tests set it with `PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', false)` and restore the saved value in `finally`; `TMultibyteStringTest` runs every method through both paths with a data provider.

## See Also

- [`TUtf8Converter`](TUtf8Converter.md) — charset conversion through iconv.
- [Key Conventions](../INDEX.md#key-conventions) — optional extensions.
