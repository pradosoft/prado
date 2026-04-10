# I18N/TChoiceFormat

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TChoiceFormat`**

## Class Info
**Location:** `framework/I18N/TChoiceFormat.php`
**Namespace:** `Prado\I18N`

## Overview
Plural/choice selection control. Maps a numeric value to a localized string based on ICU-style choice patterns.

## Usage

```php
<com:TChoiceFormat Value="1">[1] One Apple. |[2] Two Apples</com:TChoiceFormat>
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Value` | float | The numeric value used to select the choice string |

## Choice Pattern Syntax

Patterns use pipe-separated ranges:

```
value#string | value#string | value#string
```

| Notation | Meaning |
|----------|---------|
| `0#no items` | Equal to 0 |
| `1#one item` | Equal to 1 |
| `1<{0} items` | Greater than 1 (uses `{Value}` for substitution) |

### Set Notation

```php
// Range [1,2] - accepts 1 and 2
[1,2] one or two | rest

// Set {1,2,3,4} - accepts only these values
{1,2,3,4} special | rest

// Greater than or equal to negative infinity, less than 0
[-Inf,0) negative | rest
```

### Expression Syntax (since 3.1.2)

```php
{n: n % 10 > 1 && n % 10 < 5} few items | rest
```

Supports: `<`, `<=`, `>`, `>=`, `==`, `%`, `-`, `+`, `&`, `&&`, `|`, `||`, `!`

## See Also

- [TTranslate](./TTranslate.md) - Parent translation control
- [ChoiceFormat](./core/ChoiceFormat.md) - Core choice formatting logic in `core/`