# Util/Traits/TConstantReflectionTrait

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Traits](./INDEX.md) / **`TConstantReflectionTrait`**

## Class Info
**Location:** `framework/Util/Traits/TConstantReflectionTrait.php`
**Namespace:** `Prado\Util\Traits`
**Since:** 4.3.3

## Overview
`TConstantReflectionTrait` adds static reflection methods to any class that defines PHP constants. The four methods (`hasConstant`, `hasConstantValue`, `valueOfConstant`, `constantOfValue`) all share the same flexible signature: exact match, case-insensitive match, or affix-filtered match (prefix or suffix).

`ReflectionClass` instances are cached in a static `$_reflection_cache` array, so the reflection cost is paid once per class.

## Quick Example

```php
class TTextAlign
{
    use TConstantReflectionTrait;

    const Left  = 'Left';
    const Right = 'Right';
}

TTextAlign::hasConstant('Left');              // true
TTextAlign::hasConstant('left', false);       // true  (case-insensitive)
TTextAlign::hasConstantValue('Left');         // true
TTextAlign::valueOfConstant('Left');          // 'Left'
TTextAlign::constantOfValue('Left');          // 'Left'
```

## Parameter Conventions

All four methods accept the same second/third parameters:

| `$caseOrAffix` | `$caseSensitive` | Behaviour |
|----------------|------------------|-----------|
| `true` (default) | ignored | Case-sensitive exact match |
| `false` | ignored | Case-insensitive exact match |
| `'Prefix'` (string starting with letter/digit) | `true`/`false` | Match names that start with `'Prefix'` |
| `'*Suffix'` or `'-Suffix'` | `true`/`false` | Match names that end with `'Suffix'` |

## Methods

```php
static hasConstant(string $constant, bool|string $caseOrAffix = true, bool $caseSensitive = true): bool
// True if a constant with this name exists (optional affix filter on the name).

static hasConstantValue(string $value, bool|string $caseOrAffix = true, bool $caseSensitive = true): bool
// True if a constant with this value exists (optional affix filter on the name).

static valueOfConstant(string $constant, bool|string $caseOrAffix = true, bool $caseSensitive = true): ?string
// Returns the value of the named constant, or null if not found.

static constantOfValue(string $value, bool|string $caseOrAffix = true, bool $caseSensitive = true): ?string
// Returns the name of the constant with the given value, or null if not found.
```

## Affix Filtering Examples

```php
// Given: const AlignLeft = 'AlignLeft', const Left = 'Left', const AlignRight = 'AlignRight'
TMyClass::hasConstant('AlignLeft', 'Align');        // true (has prefix 'Align')
TMyClass::hasConstant('Left', 'Align');             // false
TMyClass::hasConstant('AlignLeft', 'align', false); // true (case-insensitive prefix)

// Given: const TopMargin = 'TopMargin', const BottomMargin = 'BottomMargin'
TMyClass::hasConstant('TopMargin', '*Margin');       // true (has suffix 'Margin')
TMyClass::hasConstant('BottomMargin', '-margin', false); // true (case-insensitive suffix)
```

## Patterns & Gotchas

- **String constants only** — the trait works on any constant type, but `valueOfConstant` / `constantOfValue` return `?string`; non-string constant values may produce unexpected results when compared.
- **Inherited constants included** — `ReflectionClass::getConstants()` returns constants from the entire inheritance chain, not just the declaring class.
- **Cache is per-class static** — the `$_reflection_cache` array lives in the trait, indexed by `static::class`, so each concrete class that uses the trait has its own cache slot.
