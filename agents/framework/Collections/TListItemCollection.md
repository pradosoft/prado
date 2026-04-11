# Collections/TListItemCollection

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TListItemCollection`**

## Class Info
**Location:** `framework/Collections/TListItemCollection.php`
**Namespace:** `Prado\Collections`

## Overview
TListItemCollection maintains a list of [TListItem](../Web/UI/WebControls/TListItem.md) objects for list controls like [TDropDownList](../Web/UI/WebControls/TDropDownList.md), [TListBox](../Web/UI/WebControls/TListBox.md), and [TCheckBoxList](../Web/UI/WebControls/TCheckBoxList.md).

## Inheritance

Extends [TList](./TList.md) with specific handling for `TListItem` objects.

## Key Features

- Only accepts `TListItem` objects or strings
- Creates `TListItem` objects automatically when strings are added
- Provides `createListItem()` extension point for customization

## Usage

```php
$items = new TListItemCollection();
$items->add('Option 1');  // Creates TListItem automatically
$items->add('Option 2');
$items->insertAt(0, 'New Option');

// Find by value
$index = $items->findIndexByValue('Option 1');
```

## Key Methods

### insertAt

```php
public function insertAt($index, $item): void
```

Inserts a TListItem or string at the specified index.

### findIndexByValue

```php
public function findIndexByValue(string $value, bool $includeDisabled = false): int
```

Finds the index of an item by its value.

## See Also

- [TListItem](../Web/UI/WebControls/TListItem.md) - Individual list item
- [TList](./TList.md) - Base collection class
