# Web/UI/WebControls/TSafeHtml

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSafeHtml`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSafeHtml.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TSafeHtml is a control that strips potentially dangerous HTML content using HTMLPurifier. It sanitizes body content by removing malicious code while preserving safe HTML tags and attributes.

## Key Properties/Methods

- `getConfig()` / `setConfig(\HTMLPurifier_Config)` - Custom HTMLPurifier configuration; kept in view state, about 25 KB of page state and over 100 KB once it has purified text. Without page state encryption the client receives it, `Cache.SerializerPath` included. After a postback it is finalized, so `set()` throws; set a new configuration instead. v5.0 keeps it for the current request only.
- `parseSafeHtml($text)` - Sanitizes HTML content using HTMLPurifier

## See Also

- HTMLPurifier library
