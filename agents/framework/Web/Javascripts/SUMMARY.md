# Web/Javascripts/SUMMARY.md

Client-side JavaScript/CSS package registry and asset utilities consumed by `TClientScriptManager`.

## Classes

- **`TJavaScript`** — Static utility class; methods: `renderScriptFile($url)`, `renderScriptBlock($code)`, `encode($value)`, `quoteJsLiteral($value)`, `jsonEncode($value)`; holds the script nonce and the script integrity registry.

- **`TStyleSheetHelper`** — The stylesheet counterpart: `<style>` blocks, `<link>` tags, the stylesheet integrity registry, and `minify($css)`.

- **`IMinifier`** — The minifier seam behind `TJavaScript::minify()` and `TStyleSheetHelper::minify()`; **`TJavaScriptMinifier`** (JSMin) and **`TStyleSheetMinifier`** (in-house CSS) are the defaults.

- **`TJavaScriptAsset`** — A script URL with `Async` and `Integrity` that renders its own `<script>` tag.

- **`TJavaScriptLiteral`** — Wraps a raw JavaScript expression that should NOT be quoted when embedded in JavaScript context.

- **`TJavaScriptString`** — Wraps a string value that SHOULD be quoted as a JS string literal.
