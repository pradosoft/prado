# Xml/TXmlElement

### Directories
[framework](../INDEX.md) / [Xml](./INDEX.md) / **`TXmlElement`**

## Class Info
**Location:** `framework/Xml/TXmlElement.php`
**Namespace:** `Prado\Xml`

## Overview
TXmlElement represents an XML element node. It provides properties for tag-name, attributes, and text content, along with methods for parent/child element relationships.

## Key Features
- Implements IteratorAggregate, ArrayAccess, and Countable interfaces
- Supports XPath expressions through xpath() method
- Implements DOM compatibility methods for better integration
- Provides search functionality for elements and attributes
- **Tag name validation** via `validateTagName()` method (can be overridden)
- Throws `TInvalidDataTypeException` when tag name is null
- Throws `TInvalidDataValueException` when tag name is empty

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `SEARCH_ELEMENT` | `0` | Search only in current element |
| `SEARCH_DEPTH_FIRST` | `1` | Depth-first search (top to bottom) |
| `SEARCH_BREADTH_FIRST` | `2` | Breadth-first search (level by level) |

## Constructor
```php
public function __construct(string $tagName)
```
- Initializes a new XML element with specified tag name

## Properties
- `getParent()` / `setParent()` - Gets/sets parent element
- `getTagName()` / `setTagName()` - Gets/sets tag name
- `getValue()` / `setValue()` - Gets/sets text content
- `getAttributes()` - Gets list of attributes
- `getElements()` - Gets list of child elements

## Search Methods
- `getElementByTagName()` - Gets first child element with specified tag name
- `getElementsByTagName()` - Gets all child elements with specified tag name
- `getElementByAttribute()` - Gets first child element with specified attribute
- `getElementsByAttribute()` - Gets all child elements with specified attribute
- `xpath()` - Find elements matching XPath expression

## Utility Methods
- `toString()` - Creates string representation of element
- `__toString()` - Magic method for string conversion
- `__clone()` - Creates and returns a clone of this element

## DOM Compatibility
- Implements DOMElement properties and methods
- `getNodeType()` - Gets node type
- `getNodeName()` - Gets tag name
- `getNodeValue()` - Gets text content
- `setNodeValue()` - Sets text content
- `getFirstElementChild()` - Gets first child element
- `getLastElementChild()` - Gets last child element
- `childElementCount()` - Gets number of child elements
- `getPreviousElementSibling()` - Gets previous sibling element
- `getNextElementSibling()` - Gets next sibling element

## Validation

`validateTagName()` is called by `setTagName()` to validate the tag name. Override this method in subclasses to implement custom validation rules.

## Exceptions

| Error Code | Exception | Description |
|------------|-----------|-------------|
| `xmlelement_null_tag` | `TInvalidDataTypeException` | Tag name cannot be null |
| `xmlelement_empty_tag` | `TInvalidDataValueException` | Tag name cannot be empty |