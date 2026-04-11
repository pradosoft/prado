# Web/Javascripts/INDEX.md

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / **`Javascripts/INDEX.md`**

## Purpose

Client-side JavaScript/CSS package registry and asset utilities for the Prado web layer. Defines the package dependency graph consumed by [TClientScriptManager](Web/UI/WebControls/TClientScriptManager.md) and provides PHP-side helpers for rendering script/style tags.

## PHP Classes

- **[TJavaScript](Web/Javascripts/TJavaScript.md)** — Static utility class. Methods:
  - `renderScriptFile($url)` — renders a `<script src="...">` tag.
  - `renderScriptBlock($code)` — renders an inline `<script>` block.
  - `quoteJsLiteral($value)` — safely JSON-encodes a PHP value for embedding in JavaScript.
  - `jsonEncode($value)` — wrapper around `json_encode` with Prado-standard flags.

- **[TJavaScriptAsset](Web/Javascripts/TJavaScriptAsset.md)** — Represents a published JS asset. Properties: `BaseUrl`, `Version`. Used by [TAssetManager](Web/TAssetManager.md) to generate cache-busted URLs.

- **[TJavaScriptLiteral](Web/Javascripts/TJavaScriptLiteral.md)** — Wraps a raw JavaScript expression that should **not** be quoted when embedded in a JS context (e.g., a callback function reference). Distinguishes literal code from string values in `quoteJsLiteral()`.

- **[TJavaScriptString](Web/Javascripts/TJavaScriptString.md)** — Wraps a string value that **should** be quoted as a JS string literal.

## Package Manifests

- **`packages.php`** — Returns an associative array declaring all JS packages and their dependencies:
  ```php
  'prado'       => [...source files...],
  'validator'   => [...],
  'ajax'        => [...],
  // etc.
  ```
  [TClientScriptManager](Web/UI/WebControls/TClientScriptManager.md) uses this to resolve and publish packages in dependency order.

- **`css-packages.php`** — Same structure for CSS packages (jQuery UI themes and component styles).

## JavaScript Source (`source/prado/`)

| Package | File(s) | Purpose |
|---|---|---|
| `prado` | `prado.js`, `controls/controls.js` | Framework core, jQuery OOP layer, behaviors, events |
| `ajax` | `ajax3.js`, `activecontrols3.js` | Callback/postback AJAX system |
| `validator` | `validator/validation3.js` | Client-side form validation |
| `datepicker` | `datepicker/datepicker.js` | Calendar date picker widget |
| `colorpicker` | `colorpicker/colorpicker.js` | HSB color picker widget |
| `ratings` | `ratings/ratings.js` | Star/block ratings widget |
| `htmlarea` | `controls/htmlarea.js` | TinyMCE v3/v4 rich text editor integration |
| `htmlarea5` | `controls/htmlarea5.js` | TinyMCE v5+ integration |
| `inlineeditor` | `inlineeditor.js` | Inline content editing |
| `activedatepicker` | `activedatepicker.js` | AJAX-enhanced date picker |
| `activefileupload` | `activefileupload.js` | AJAX file upload with progress |
| `keyboard` | `controls/keyboard.js` | Virtual on-screen keyboard |
| `slider` | `controls/slider.js` | Range slider |
| `accordion` | `controls/accordion.js` | Expand/collapse panels |
| `tabpanel` | `controls/tabpanel.js` | Tabbed interface |

External packages (jQuery, jQuery UI, TinyMCE, HighlightJS, Clipboard) are sourced from `bower_components` and declared in `packages.php`.

## Patterns & Gotchas

- **Never reference JS files directly** — always use the package name via [TClientScriptManager::registerPackage()](Web/UI/WebControls/TClientScriptManager.md). The manager resolves dependencies, deduplicates, and handles publishing.
- **TJavaScriptLiteral** must be used for any PHP value that is already valid JavaScript (function references, pre-encoded JSON). Passing raw strings through `quoteJsLiteral()` will double-encode them.
- **Asset versioning** — [TJavaScriptAsset::getVersion()](Web/Javascripts/TJavaScriptAsset.md) appends a version query parameter to bust browser caches on upgrade. Update it when making breaking JS changes.
