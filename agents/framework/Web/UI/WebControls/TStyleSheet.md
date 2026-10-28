# Web/UI/WebControls/TStyleSheet

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TStyleSheet`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TStyleSheet.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TStyleSheet represents a link to a stylesheet file and/or inline CSS code. It supports importing bundled PRADO CSS libraries (like jQuery UI), external stylesheet URLs, and inline CSS content from child controls.

## Key Properties/Methods

- `getStyleSheetUrl()` / `setStyleSheetUrl(string)` - URL to external stylesheet
- `getPradoStyles()` / `setPradoStyles(string)` - Comma-delimited list of bundled CSS libraries
- `getMediaType()` / `setMediaType(string)` - CSS media type (e.g., 'screen', 'print')

## CSP

The `<style>` of the child CSS and the stylesheet `<link>` carry the CSP nonce through [TStyleSheetHelper](../../Javascripts/TStyleSheetHelper.md) (@since 4.4.0). A TStyleSheet rendered again in a callback carries the callback request's nonce, which the page's policy does not allow; CSS that changes in a callback is registered with `TClientScriptManager::registerStyleSheet()`.

## See Also

- [TControl](../TControl.md)
