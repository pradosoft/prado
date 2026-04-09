# TAttributeCollection

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TAttributeCollection](./TAttributeCollection.md)

**Location:** `framework/Collections/TAttributeCollection.php`
**Namespace:** `Prado\Collections`

## Overview

TAttributeCollection extends [TMap](./TMap.md) to provide a collection for storing attribute names and values. It allows accessing and setting attribute values like object properties.

## Key Features

- Case-insensitive attribute names (converted to lowercase)
- Property-style access: `$collection->Text` instead of `$collection->add('Text', 'value')`
- Extends TMap functionality

## Usage

```php
$attrs = new TAttributeCollection();
$attrs->add('class', 'my-class');
$attrs->add('id', 'main');

// Property-style access
$attrs->Text = 'Hello';  // Same as $attrs->add('Text', 'Hello')
echo $attrs->Text;        // Same as $attrs->itemAt('Text')

// Case-insensitive
$attrs->text = 'World';
echo $attrs->TEXT;  // 'World'
```

## Properties

### CaseSensitive

By default, keys are case-insensitive. Set to `true` for case-sensitive behavior:

```php
$attrs->setCaseSensitive(true);
```

## Extends

[TMap](./TMap.md) - All TMap methods are available including `add()`, `remove()`, `itemAt()`, etc.

## See Also

- [TMap](./TMap.md) - Base key-value collection
