# Web/Javascripts/IMinifier

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Javascripts](./INDEX.md) / **`IMinifier`**

## Class Info
**Location:** `framework/Web/Javascripts/IMinifier.php`
**Namespace:** `Prado\Web\Javascripts`
**Since:** 4.4.0

## Overview
The seam for minifying source text: one method, `minify(string $code): string`, that returns text with the same meaning and without the comments and whitespace it does not need.

| Consumer | Minifier | Default |
|---|---|---|
| `TJavaScript::minify()` | `TJavaScript::getMinifier()` / `setMinifier(?IMinifier)` | [TJavaScriptMinifier](TJavaScriptMinifier.md) |
| `TStyleSheetHelper::minify()` | `TStyleSheetHelper::getMinifier()` / `setMinifier(?IMinifier)` | [TStyleSheetMinifier](TStyleSheetMinifier.md) |
| `TClientScriptManager::minifiedPackageFile()` | the minifier of the file's language | — |

- `setMinifier()` is static and lasts for the PHP process; `null` restores the default.
- The JavaScript and stylesheet minifiers are separate.
- An application replaces either one with its own `IMinifier`, such as a wrapper of an external minifier library.
