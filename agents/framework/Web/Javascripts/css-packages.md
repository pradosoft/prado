# Web/Javascripts/css-packages

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Javascripts](INDEX.md) / **`css-packages`**

**Location:** `framework/Web/Javascripts/css-packages.php`

## Overview
This file defines the CSS package registry for PRADO. It declares all CSS packages, their source files, folder mappings, and dependencies. Consumed by `TClientScriptManager` to resolve and publish CSS packages in dependency order.

## Package Folders

| Alias | Path |
|-------|------|
| `jquery-ui` | `Vendor\bower-asset\jquery-ui` |
| `highlightjs` | `Vendor\bower-asset\highlightjs` |

## Packages

| Package | Files |
|---------|-------|
| `jquery-ui` | `jquery-ui/themes/base/jquery-ui.css` |
| `jquery.ui.accordion` | `jquery-ui/themes/base/accordion.css` |
| `jquery.ui.autocomplete` | `jquery-ui/themes/base/autocomplete.css` |
| `jquery.ui.button` | `jquery-ui/themes/base/button.css` |
| `jquery.ui.core` | `jquery-ui/themes/base/core.css` |
| `jquery.ui.datepicker` | `jquery-ui/themes/base/datepicker.css` |
| `jquery.ui.dialog` | `jquery-ui/themes/base/dialog.css` |
| `jquery.ui.menu` | `jquery-ui/themes/base/menu.css` |
| `jquery.ui.progressbar` | `jquery-ui/themes/base/progressbar.css` |
| `jquery.ui.resizable` | `jquery-ui/themes/base/resizable.css` |
| `jquery.ui.selectable` | `jquery-ui/themes/base/selectable.css` |
| `jquery.ui.slider` | `jquery-ui/themes/base/slider.css` |
| `jquery.ui.spinner` | `jquery-ui/themes/base/spinner.css` |
| `jquery.ui.tabs` | `jquery-ui/themes/base/tabs.css` |
| `jquery.ui.theme` | `jquery-ui/themes/base/theme.css` |
| `jquery.ui.tooltip` | `jquery-ui/themes/base/tooltip.css` |
| `highlightjs` | `highlightjs/styles/default.css` |

## Dependencies

Each `jquery.ui.<component>` package loads `jquery.ui.core` and its own stylesheet; `jquery-ui` loads the whole theme. The file names are those jQuery UI ships in `themes/base` since 1.11. `TJavaScriptPackagesTest` checks that every listed file exists.

## See Also

- [packages.php](./packages.md) - JavaScript package registry
- [TClientScriptManager](../UI/TClientScriptManager.md) — Consumes this registry
