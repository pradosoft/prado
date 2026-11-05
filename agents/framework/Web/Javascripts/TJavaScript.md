# Web/Javascripts/TJavaScript

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Javascripts](./INDEX.md) / **`TJavaScript`**

## Class Info
**Location:** `framework/Web/Javascripts/TJavaScript.php`
**Namespace:** `Prado\Web\Javascripts`
**Since:** 3.0

## Overview
`TJavaScript` is a static utility class that renders, encodes, and minimises JavaScript for Prado's client-side infrastructure. It handles script-file `<script src>` tags, inline `<script>` blocks, JSON encoding/decoding, JavaScript string quoting, JavaScript literal markers, and JSMin-based minification. All methods are static; the class is never instantiated.

## Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `static renderScriptFiles(array $files): string` | `string` | Renders a list of `<script src="…">` tags for the given URLs. |
| `static renderScriptFile(TJavaScriptAsset\|string $asset): string` | `string` | Renders a single `<script src="…">` tag for a URL or `TJavaScriptAsset`. |
| `static renderScriptBlocks(array $scripts): string` | `string` | Wraps an array of JavaScript blocks in a single `<script>` tag. |
| `static renderScriptBlocksCallback(array $scripts): string` | `string` | Renders JavaScript blocks for an Ajax callback response. |
| `static renderScriptBlock(string $script): string` | `string` | Wraps a single JavaScript block in a `<script>` tag. |
| `static quoteString(string $js): string` | `string` | Escapes and wraps `$js` in double quotes so it is safe as a JavaScript string literal. |
| `static quoteJsLiteral(mixed $js): TJavaScriptLiteral` | `TJavaScriptLiteral` | Marks `$js` as a raw JavaScript expression, preventing further encoding by `encode()`. |
| `static isJsLiteral(mixed $js): bool` | `bool` | Returns `true` when `$js` has been marked as a JavaScript literal. |
| `static encode(mixed $value, bool $toMap = true, bool $encodeEmptyStrings = false): string` | `string` | Encodes a PHP variable into its JavaScript representation. @since 3.1.5 |
| `static jsonEncode(mixed $value, mixed $options = 0): string` | `string` | Encodes `$value` via `json_encode()` and returns the JSON string. |
| `static jsonDecode(string $value, bool $assoc = false, int $depth = 512): mixed` | `mixed` | Decodes a JSON string via `json_decode()` and returns the PHP variable. |
| `static JSMin(string $code): string` | `string` | Minimises JavaScript source using a Douglas Crockford JSMin implementation. |

## See Also

- `TJavaScriptAsset` — represents a versioned script asset file
- `TJavaScriptLiteral` — marker class for raw JS expressions
- [`TClientScriptManager`](../UI/TClientScriptManager.md) — orchestrates script registration and uses this class for rendering
