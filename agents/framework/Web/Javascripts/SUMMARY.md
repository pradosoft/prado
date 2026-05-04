# Web/Javascripts/SUMMARY.md

Client-side JavaScript/CSS package registry and asset utilities consumed by `TClientScriptManager`.

## Classes

- **`TJavaScript`** — Static utility class; methods: `renderScriptFile($url)`, `renderScriptBlock($code)`, `quoteJsLiteral($value)`, `jsonEncode($value)`.

- **`TJavaScriptAsset`** — Represents a published JS asset; properties: `BaseUrl`, `Version`.

- **`TJavaScriptLiteral`** — Wraps a raw JavaScript expression that should NOT be quoted when embedded in JavaScript context.

- **`TJavaScriptString`** — Wraps a string value that SHOULD be quoted as a JS string literal.
