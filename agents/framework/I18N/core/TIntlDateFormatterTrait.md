# I18N/core/TIntlDateFormatterTrait

### Directories
[framework](../../INDEX.md) / [I18N](../INDEX.md) / [core](./INDEX.md) / **`TIntlDateFormatterTrait`**

## Class Info
**Location:** `framework/I18N/core/TIntlDateFormatterTrait.php`
**Namespace:** `Prado\I18N\core`
**Since:** `4.3.3`

## Overview
Trait providing a caching factory for PHP's `\IntlDateFormatter`. Classes that need to format dates in multiple locales or with multiple format type combinations can `use` this trait to avoid constructing a new `IntlDateFormatter` on every render.

Currently used by [TDateFormat](../TDateFormat.md).

## Static Cache

```php
protected static array $formatters = [];
```

Keyed as `$formatters[$culture][$datetype][$timetype]` → `\IntlDateFormatter` instance. Shared across all instances of the host class (static property on each using class).

## Key Method

### `getIntlDateFormatter(string $culture, int $datetype, int $timetype): ?\IntlDateFormatter`

Returns a cached `IntlDateFormatter` for the given combination of culture and format type constants.

- Returns `null` if the `IntlDateFormatter` class does not exist (i.e., the `intl` extension is not loaded).
- Creates and caches a new `IntlDateFormatter($culture, $datetype, $timetype)` on first call for each unique triple.

**Parameters:**
- `$culture` — POSIX locale string (e.g., `'en_US'`, `'de_DE'`)
- `$datetype` — One of: `IntlDateFormatter::FULL`, `LONG`, `MEDIUM`, `SHORT`, `NONE`
- `$timetype` — Same constants as `$datetype`

**See:** PHP manual [IntlDateFormatter constants](https://www.php.net/manual/en/class.intldateformatter.php)

## Usage

```php
class MyControl extends TControl
{
    use TIntlDateFormatterTrait;

    protected function render($writer): void
    {
        $fmt = $this->getIntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT);
        if ($fmt !== null) {
            $writer->write($fmt->format(new \DateTime()));
        }
    }
}
```

## Gotchas

- **`intl` extension required** — `getIntlDateFormatter()` returns `null` silently when the extension is absent. Callers must handle `null`.
- **Cache is per-class, not per-object** — since `$formatters` is `static`, all instances of the same class share one cache. This is generally desirable (formatter construction is expensive) but means the cache grows without bound in long-running processes.
- **Custom patterns** — when a custom ICU pattern is needed (e.g., `TDateFormat` with a non-preset `Pattern`), the cached instance is not used; callers construct a fresh `IntlDateFormatter` and call `setPattern()` on it themselves.

## See Also

- [TDateFormat](../TDateFormat.md) - Primary consumer of this trait
- [TI18NControlTrait](../TI18NControlTrait.md) - Companion trait for Culture/Charset resolution
