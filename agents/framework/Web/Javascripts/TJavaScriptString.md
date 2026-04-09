# TJavaScriptString

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Javascripts](./INDEX.md) > [TJavaScriptString](./TJavaScriptString.md)

**Location:** `framework/Web/Javascripts/TJavaScriptString.php`
**Namespace:** `Prado\Web Javascripts`

## Overview

TJavaScriptString is an internal class that marks strings which will be forcibly encoded when rendered inside a JavaScript block. It extends `TJavaScriptLiteral` and overrides `toJavaScriptLiteral()` to JSON-encode the string with hex escaping for quotes, apostrophes, and tags.

## Key Properties/Methods

- `toJavaScriptLiteral()` - Returns the JSON-encoded, hex-escaped string

## Usage

This class is used internally by PRADO to ensure strings are properly encoded for JavaScript contexts. Unlike `TJavaScriptLiteral`, this class encodes the string value.

## See Also

- [TJavaScriptLiteral](./TJavaScriptLiteral.md) - For raw JavaScript that should NOT be encoded
- [TJavaScript](./TJavaScript.md) - The main JavaScript utility class
