# Web/Javascripts/TJavaScriptMinifier

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Javascripts](./INDEX.md) / **`TJavaScriptMinifier`**

## Class Info
**Location:** `framework/Web/Javascripts/TJavaScriptMinifier.php`
**Namespace:** `Prado\Web\Javascripts`
**Since:** 4.4.0

## Overview
The default [IMinifier](IMinifier.md) of `TJavaScript::minify()`. It runs `\JSMin\JSMin::minify()` from `mrclay/jsmin-php`, as `TJavaScript::JSMin()` does.

- Source JSMin cannot parse throws `\JSMin\UnterminatedStringException`, `UnterminatedCommentException`, or `UnterminatedRegExpException`.
- `TJavaScript::JSMin()` always runs JSMin; `TJavaScript::minify()` runs the configured minifier.

## Tests
- `tests/unit/Web/Javascripts/TJavaScriptTest.php`
