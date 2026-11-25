# Util/TMultibyteString

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TMultibyteString`**

## Class Info
**Location:** `framework/Util/TMultibyteString.php`
**Namespace:** `Prado\Util`
**Final** static helper, `@since 4.4.0`.

## Overview

Measures, cuts, and case-maps UTF-8 strings by character, and cuts them by byte on character boundaries. `composer.json` suggests `mbstring` and requires `pcre` and `intl`, so each method calls the mbstring function when the extension is loaded and otherwise reaches the same result through the required extensions.

| Method | With mbstring | Without mbstring |
|---|---|---|
| `length(string): int` | `mb_strlen()` | the code points `preg_match_all('/./su')` matches |
| `substr(string, int $start, ?int $length): string` | `mb_substr()` | `preg_split('//u')` into code points, then `array_slice()` |
| `cut(string, int $start, ?int $length): string` | `mb_strcut()` | byte `substr()`, with the start and the end moved back over UTF-8 continuation bytes (`10xxxxxx`) |
| `upper(string): string` | `mb_strtoupper()` | the ICU `Any-Upper` transliterator |
| `lower(string): string` | `mb_strtolower()` | the ICU `Any-Lower` transliterator |
| `hasMbstring(): bool` | the resolved flag | |

Both paths apply the full Unicode case mapping (`upper('ß')` is `SS`, `lower('İ')` is `i̇`). A string that is not valid UTF-8 is measured and cut in bytes and cased with `strtoupper()` and `strtolower()`. The transliterators are created once per rule and kept in a static.

## Cutting to a byte limit

`substr()` counts characters, and PHP's `substr()` counts bytes and can split a character. `cut()` counts bytes and keeps whole characters, for a field whose size is bounded in bytes.

```php
TMultibyteString::cut(str_repeat('日', 100), 0, 256);   // 85 characters, 255 bytes
TMultibyteString::cut('aé', 0, 2);                      // 'a'
```

| Argument | Result |
|---|---|
| `$start` inside a character | moves back to the first byte of that character |
| `$start` negative | counts from the end, then moves back |
| `$length` | the end is `$length` bytes after the moved start; an end inside a character moves back |
| `$length` negative | leaves that many bytes off the end, measured from `$start` as given |
| `$length` null | to the end of the string |

`cut()` tests the string with `preg_match('//u')` first and cuts a string that is not valid UTF-8 with `substr()` on both paths. `mb_strcut()` reads such a string by its lead bytes, and its result differs between PHP versions.

## Callers

| Caller | Use |
|---|---|
| [`TKeyShortcuts`](../Web/UI/WebControls/TKeyShortcuts.md) `normalizeKey()` | one-character key tokens and their uppercase |
| [`TBaseTerm`](../Web/UI/WebControls/TBaseTerm.md) `getTermKey()` | case-insensitive term keys |
| [`TWebControl`](../Web/UI/WebControls/TWebControl.md) `setAccessKey()` | the one-character check |
| [`TRangeValidator`](../Web/UI/WebControls/TRangeValidator.md) `isValidStringLength()` | `StringLength`; a value of another `Charset` is converted to UTF-8 by [`TUtf8Converter`](TUtf8Converter.md) first |
| [`TSimpleDateFormatter`](TSimpleDateFormatter.md) `length()`, `substring()` | pattern and value slicing; another `Charset` is converted to UTF-8 by [`TUtf8Converter`](TUtf8Converter.md) first |

## Testing the fallback

`_mbstring` is a private static `?bool` resolved on first use. Tests set it with `PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', false)` and restore the saved value in `finally`; `TMultibyteStringTest` runs every method through both paths with a data provider, and compares the two paths of `cut()` over every start and length of a set of strings.

## See Also

- [`TUtf8Converter`](TUtf8Converter.md) — charset conversion through iconv.
- [Key Conventions](../INDEX.md#key-conventions) — optional extensions.
