# css-packages.php

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Javascripts](./INDEX.md) > [css-packages](./css-packages.md)

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
| `jquery.ui.accordion` | `jquery-ui/themes/base/jquery.ui.accordion.css` |
| `jquery.ui.autocomplete` | `jquery-ui/themes/base/jquery.ui.autocomplete.css` |
| `jquery.ui.button` | `jquery-ui/themes/base/jquery.ui.button.css` |
| `jquery.ui.core` | `jquery-ui/themes/base/jquery.ui.core.css` |
| `jquery.ui.datepicker` | `jquery-ui/themes/base/jquery.ui.datepicker.css` |
| `jquery.ui.dialog` | `jquery-ui/themes/base/jquery.ui.dialog.css` |
| `jquery.ui.menu` | `jquery-ui/themes/base/jquery.ui.menu.css` |
| `jquery.ui.progressbar` | `jquery-ui/themes/base/jquery.ui.progressbar.css` |
| `jquery.ui.resizable` | `jquery-ui/themes/base/jquery.ui.resizable.css` |
| `jquery.ui.selectable` | `jquery-ui/themes/base/jquery.ui.selectable.css` |
| `jquery.ui.slider` | `jquery-ui/themes/base/jquery.ui.slider.css` |
| `jquery.ui.spinner` | `jquery-ui/themes/base/jquery.ui.spinner.css` |
| `jquery.ui.tabs` | `jquery-ui/themes/base/jquery.ui.tabs.css` |
| `jquery.ui.theme` | `jquery-ui/themes/base/jquery.ui.theme.css` |
| `jquery.ui.tooltip` | `jquery-ui/themes/base/jquery.ui.tooltip.css` |
| `highlightjs` | `highlightjs/styles/default.css` |

## Dependencies

All jQuery UI component packages depend on `jquery.ui.core`, which depends on `jquery-ui` itself.

## See Also

- [packages.php](./packages.md) - JavaScript package registry
- [TClientScriptManager](../UI/TClientScriptManager.php) - Consumes this registry
