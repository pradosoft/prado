# TXmlElementList Class

### Directories
[./](../INDEX.md) > [Xml](./INDEX.md) > [TXmlElementList](./TXmlElementList.md)

## Overview
[TXmlElement](./TXmlElement.md)List represents a collection of [TXmlElement](./TXmlElement.md) objects. It extends [TList](../Collections/TList.md) and adds proper parent/child relationship handling for XML elements.

## Key Features
- Extends [TList](../Collections/TList.md) for collection operations
- Manages parent/child relationships for XML elements
- Handles addition and removal of elements with proper relationship management
- Maintains proper parent references when adding/removing elements

## Constructor
```php
public function __construct(?[TXmlElement](./TXmlElement.md) $owner)
```
- Initializes a new [TXmlElement](./TXmlElement.md)List with specified owner element

## Methods

### Collection Management
- `insertAt($index, $item)` - Inserts item at specified position with parent relationship handling
- `removeAt($index)` - Removes item at specified position with cleanup

### Properties
- `getOwner()` - Gets the owner of this list

## Parent/Child Relationship Management
- When inserting elements, it properly sets the parent reference
- When removing elements, it clears the parent reference
- Handles moving elements between different parent lists properly