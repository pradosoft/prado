# I18N / core / ChoiceFormat

### Directories
[./](../INDEX.md) > [I18N](../INDEX.md) > [core](./INDEX.md) > [ChoiceFormat](./ChoiceFormat.md)

**Location:** `framework/I18N/core/ChoiceFormat.php`
**Namespace:** `Prado\I18N\core`

## Overview

Evaluates ICU-style plural/interval expressions. Used by `MessageFormat` for pluralization. Maps numeric ranges to localized string choices.

## Usage

```php
$choice = new ChoiceFormat();
$pattern = "0#no items|1#one item|1<{0} items";

echo $choice->format($pattern, 0);  // "no items"
echo $choice->format($pattern, 1);  // "one item"
echo $choice->format($pattern, 5);  // "5 items"
```

## Pattern Syntax

### Basic Ranges

| Pattern | Meaning |
|---------|---------|
| `0#none` | Exactly 0 |
| `1#one` | Exactly 1 |
| `2+#many` | 2 or more |

### Set Notation

| Pattern | Meaning |
|---------|---------|
| `[1,2]` | Between 1 and 2 (inclusive) |
| `(1,2)` | Between 1 and 2 (exclusive) |
| `{1,2,3}` | One of 1, 2, or 3 |
| `[-Inf,0)` | Less than 0 |

### Expression Syntax (3.1.2+)

```php
{n: n % 10 > 1 && n % 10 < 5}  // matches 2,3,4,22,23,24
```

## Key Methods

### `format($string, $number)`

Find and return the string that matches the number.

### `parse($string)`

Returns `[$sets, $strings]` arrays from a choice pattern.

### `isValid($number, $set)`

Test if a number belongs to a set notation.

## See Also

- [MessageFormat](./MessageFormat.md) - Uses ChoiceFormat for plurals
- [TChoiceFormat](../TChoiceFormat.md) - Template control wrapper