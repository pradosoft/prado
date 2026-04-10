# Xml/SUMMARY.md

DOM-compatible XML document and element manipulation for configuration parsing, template processing, and XML handling.

## Classes

- **`TXmlDocument`** — Extends `TXmlElement` wrapping `DOMDocument`; methods: `loadFromFile($path)`, `loadFromString($xml)`, `saveToFile($path)`, `saveToString()`.

- **`TXmlElement`** — Represents a single XML element with `TagName`, `Value`, `Attributes` (`TMap`), `Elements` (`TXmlElementList`), `Parent`, and `xpath($expression)` method.

- **`TXmlElementList`** — `TList` subclass holding child `TXmlElement` objects; maintains `Parent` references.
