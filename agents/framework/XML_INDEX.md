# Xml/INDEX.md - XML_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

DOM-compatible XML document and element manipulation for the Prado framework. Used throughout the framework for configuration parsing, template processing, and application-level XML handling.

## Classes

- **`TXmlDocument`** — Extends `TXmlElement`. Wraps `DOMDocument`. Load via `loadFromFile($path)` or `loadFromString($xml)`. Save via `saveToFile($path)` or `saveToString()` / `__toString()`. Properties: `Version`, `Encoding`. The `TagName` property is the root element name.

- **`TXmlElement`** — Represents a single XML element. Key properties and features:
  - `TagName` — element tag name
  - `Value` — text content (`nodeValue`; not CDATA or comments)
  - `Attributes` — `TMap` of name→value attribute pairs
  - `Elements` — `TXmlElementList` of direct child elements
  - `Parent` — reference to parent `TXmlElement`
  - `xpath($expression)` — run XPath query; returns array of `TXmlElement`.
  - Implements `ArrayAccess` (index into child elements), `Countable`, `IteratorAggregate`
  - DOM compatibility: `getNodeType()`, node type constants (`XML_DOCUMENT_NODE`, etc.), partial DOM property support
  - Element Search modes: `SEARCH_ELEMENT`, `SEARCH_DEPTH_FIRST`, `SEARCH_BREADTH_FIRST`

- **`TXmlElementList`** — `TList` subclass holding child `TXmlElement` objects. Maintains `Parent` references. Handles re-insertion correctly (removing from self before re-adding to avoid duplicates).

## Patterns

- **Creating documents:**
  ```php
  $doc = new TXmlDocument('1.0', 'utf-8');
  $doc->TagName = 'root';
  $child = new TXmlElement('item');
  $child->Attributes['id'] = '1';
  $child->Value = 'Hello';
  $doc->Elements[] = $child;
  echo $doc->saveToString();
  ```

- **Iterating children:**
  ```php
  foreach ($element->Elements as $child) { ... }
  // or array-style:
  $first = $element[0];
  $count = count($element);
  ```

- **XPath:**
  ```php
  $results = $doc->xpath('//item[@id="1"]');
  ```

## Gotchas

- `TXmlDocument` inherits from `TXmlElement` — the root element *is* the document, so `TagName` on a `TXmlDocument` is the root tag.
- `Value` only returns text node content — CDATA sections and comments are not included.
- XPath adds (then removes) `prado-xml-id-*` attributes internally for node tracking; treat these as internal.
- libxml errors are suppressed internally (`libxml_use_internal_errors(true)`) during parsing — check the return value of `loadFromString()`/`loadFromFile()` rather than expecting exceptions on malformed XML.
