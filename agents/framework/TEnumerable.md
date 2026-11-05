# TEnumerable

### Directories
[framework](./INDEX.md) / **`TEnumerable`**

## Class Info
**Location:** `framework/TEnumerable.php`
**Namespace:** `Prado`
**Implements:** `Iterator`
**Uses:** `Prado\Util\Traits\TConstantReflectionTrait`

## Overview
Base class for all Prado enumerable types. Define an enumerable by extending `TEnumerable` and declaring string constants — each constant is one valid value. Instances are iterable over all constants.

As of 4.3.3, `TEnumerable` uses `TConstantReflectionTrait`, which adds static methods to look up constants by name or value.

## Defining an Enum

```php
class TTextAlign extends \Prado\TEnumerable
{
	const Left = 'Left';
	const Right = 'Right';
	const Center = 'Center';
}
```

Use the constants directly:
```php
$align = TTextAlign::Left;    // 'Left'
```

## Static Methods (via TConstantReflectionTrait) @since 4.3.3

| Method | Description |
|--------|-------------|
| `hasConstant(string $name): bool` | Returns `true` if a constant with this name exists |
| `hasConstantValue(string $value): bool` | Returns `true` if any constant has this value |
| `valueOfConstant(string $name): ?string` | Returns the value of a constant by name, or `null` |
| `constantOfValue(string $value): ?string` | Returns the constant name for a given value, or `null` |

```php
TTextAlign::hasConstant('Left');              // true
TTextAlign::hasConstant('Unknown');           // false
TTextAlign::valueOfConstant('Left');          // 'Left'
TTextAlign::constantOfValue('Left');          // 'Left'
TTextAlign::hasConstantValue('Right');        // true
```

All four methods accept optional `$caseOrAffix` and `$caseSensitive` parameters (from `TConstantReflectionTrait`) for prefix/suffix filtering when a class has many constants.

## Iterator Interface

Instances can be iterated over all constants:

```php
foreach (new TTextAlign() as $name => $value) {
	echo "$name => $value\n";
	// Left => Left
	// Right => Right
	// Center => Center
}
```

## Framework Usage

Property setters that expect an enum value typically call `TPropertyValue::ensureEnum($value, EnumClass::class)`, which validates that the incoming string matches a defined constant.

## See Also
- `Prado\Util\Traits\TConstantReflectionTrait` — provides the static constant reflection methods
- `TPropertyValue::ensureEnum()` — validates enum values at runtime
