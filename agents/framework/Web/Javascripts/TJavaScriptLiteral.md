# Web/Javascripts/TJavaScriptLiteral

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Javascripts](./INDEX.md) / **`TJavaScriptLiteral`**

## Class Info
**Location:** `framework/Web/Javascripts/TJavaScriptLiteral.php`
**Namespace:** `Prado\Web\Javascripts`

## Overview
TJavaScriptLiteral encloses string literals that should not be escaped by `TJavaScript::encode()`. Since Prado 3.2, all data sent to the client-side is encoded by default to prevent injection attacks. This class allows bypassing that encoding for raw JavaScript code.

## Key Properties/Methods

- `__construct($s)` - Creates a literal wrapper around the given string
- `__toString()` - Returns the raw string value
- `toJavaScriptLiteral()` - Returns the string for JavaScript context

## Usage

```php
$js = "alert('hello')";
$raw = new TJavaScriptLiteral($js);
// or shorthand: $raw = _js($js);
```

## See Also

- [TJavaScriptString](./TJavaScriptString.md) - For strings that SHOULD be encoded
- [TJavaScript](./TJavaScript.md) - The encoding utility class
