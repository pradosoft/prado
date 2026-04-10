# Web/Javascripts/packages

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / [Javascripts](./Web/Javascripts/INDEX.md) / **`packages`**

**Location:** `framework/Web/Javascripts/packages.php`

## Overview
This file defines the JavaScript package registry for PRADO. It declares all JS packages, their source files, folder mappings, and dependencies. Consumed by `TClientScriptManager` to resolve and publish packages in dependency order.

## Package Folders

| Alias | Path |
|-------|------|
| `prado` | `Prado\Web\Javascripts\source\prado` |
| `jquery` | `Vendor\bower-asset\jquery\dist` |
| `jquery-ui` | `Vendor\bower-asset\jquery-ui` |
| `tinymce` | `Vendor\bower-asset\tinymce` |
| `highlightjs` | `Vendor\bower-asset\highlightjs` |
| `clipboard` | `Vendor\bower-asset\clipboard\dist` |

## Packages

| Package | Files |
|---------|-------|
| `prado` | `prado/prado.js`, `prado/controls/controls.js` |
| `logger` | `prado/logger/logger.js` |
| `validator` | `prado/validator/validation3.js` |
| `ajax` | `prado/activecontrols/ajax3.js`, `prado/activecontrols/activecontrols3.js` |
| `datepicker` | `prado/datepicker/datepicker.js` |
| `colorpicker` | `prado/colorpicker/colorpicker.js` |
| `slider` | `prado/controls/slider.js` |
| `keyboard` | `prado/controls/keyboard.js` |
| `tabpanel` | `prado/controls/tabpanel.js` |
| `accordion` | `prado/controls/accordion.js` |
| `htmlarea` | `prado/controls/htmlarea.js` |
| `htmlarea5` | `prado/controls/htmlarea5.js` |
| `ratings` | `prado/ratings/ratings.js` |
| `inlineeditor` | `prado/activecontrols/inlineeditor.js` |
| `activefileupload` | `prado/activefileupload/activefileupload.js` |
| `activedatepicker` | `prado/activecontrols/activedatepicker.js` |

## Dependencies

Key dependencies include: `prado` requires `jquery`, `ajax` requires `jquery` and `prado`, `activedatepicker` requires `jquery`, `prado`, `datepicker`, and `ajax`.

## See Also

- [css-packages.php](./css-packages.md) - CSS package registry
- [TClientScriptManager](../UI/TClientScriptManager.php) - Consumes this registry
