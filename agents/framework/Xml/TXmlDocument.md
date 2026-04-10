# Xml/TXmlDocument

### Directories
[framework](../INDEX.md) / [Xml](./INDEX.md) / **`TXmlDocument`**

## Class Info
**Location:** `framework/Xml/TXmlDocument.php`
**Namespace:** `Prado\Xml`
**Extends:** `[TXmlElement](./TXmlElement.md)`

## Overview
Represents an entire XML document. Extends `[TXmlElement](./TXmlElement.md)` so the root element *is* the document — `TagName` on the document is the root tag name. Internally backed by PHP's `DOMDocument`.

Related classes: `[TXmlElement](./TXmlElement.md)`, `[TXmlElementList](./TXmlElementList.md)`.

## Creating Documents

```php
$doc = new TXmlDocument('1.0', 'utf-8');
$doc->TagName = 'Config';

$module = new [TXmlElement](./TXmlElement.md)('module');
$module->setAttribute('id', 'auth');
$module->setAttribute('class', 'TAuthManager');
$doc->Elements[] = $module;

echo $doc->saveToString();
// <?xml version="1.0" encoding="utf-8"?>
// <Config><module id="auth" class="TAuthManager"/></Config>
```

## Loading Documents

```php
$doc = new TXmlDocument();
$doc->loadFromFile('/path/to/config.xml');
// or
$doc->loadFromString($xmlString);
```

Returns `true` on success. libxml errors are suppressed internally (`libxml_use_internal_errors(true)`) — check return value rather than expecting exceptions on malformed XML.

## Key Properties

| Property | Description |
|----------|-------------|
| `Version` | XML version string (default `'1.0'`) |
| `Encoding` | Encoding string (default `'utf-8'`) |
| `TagName` | Root element tag name (inherited from `[TXmlElement](./TXmlElement.md)`) |
| `Elements` | `[TXmlElementList](./TXmlElementList.md)` of root's direct children |
| `Attributes` | `[TMap](../Collections/TMap.md)` of root element attributes |

## Key Methods (from TXmlDocument)

| Method | Description |
|--------|-------------|
| `loadFromFile($path)` | Load and parse XML file |
| `loadFromString($xml)` | Parse XML string |
| `saveToFile($path)` | Write XML to file |
| `saveToString()` | Return XML as string |
| `__toString()` | Alias for `saveToString()` |

## [TXmlElement](./TXmlElement.md) Features (inherited)

### XPath

```php
$results = $doc->xpath('//module[@class="TAuthManager"]');
// Returns array of [TXmlElement](./TXmlElement.md)
```

XPath temporarily adds `prado-xml-id-*` attributes for node tracking — do not rely on these.

### ArrayAccess / Countable / IteratorAggregate

```php
$first = $element[0];           // first child element
$count = count($element);       // child count
foreach ($element as $child) {} // iterate children
```

### Attribute Access

```php
$val = $element->getAttribute('id');
$element->setAttribute('id', 'auth');
$element->hasAttribute('id');
$element->removeAttribute('id');
$element->getAttributes();   // TMap
```

### Element Search

```php
$element->getElements();                           // [TXmlElementList](./TXmlElementList.md) of direct children
$element->getElementByID('myId');                  // find descendant by id attr
$element->getElementsByTagName('module');          // find all descendants with tag
$element->getElementsByAttribute('class', 'Foo'); // find by attribute value
```

### DOM Compatibility

`getNodeType()` returns `XML_DOCUMENT_NODE` (9) for `TXmlDocument`, `XML_ELEMENT_NODE` (1) for `[TXmlElement](./TXmlElement.md)`.

## Patterns & Gotchas

- **Root element = document** — `$doc->TagName` sets the root tag. Elements are added directly to `$doc->Elements`.
- **`Value` is text content only** — CDATA sections and comments are not included in `Value`.
- **libxml error handling** — malformed XML returns `false` from `loadFromString()`; it does not throw. Always check return value.
- **XPath internal attributes** — `prado-xml-id-*` attributes appear transiently during XPath calls; never persist them.
- **Serialisation** — `TXmlDocument` is serialisable; used by Prado's template/config cache.
