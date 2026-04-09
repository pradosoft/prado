# TSafeHtml

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TSafeHtml](./TSafeHtml.md)

**Location:** `framework/Web/UI/WebControls/TSafeHtml.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TSafeHtml is a control that strips potentially dangerous HTML content using HTMLPurifier. It sanitizes body content by removing malicious code while preserving safe HTML tags and attributes.

## Key Properties/Methods

- `getConfig()` / `setConfig(\HTMLPurifier_Config)` - Custom HTMLPurifier configuration
- `parseSafeHtml($text)` - Sanitizes HTML content using HTMLPurifier

## See Also

- HTMLPurifier library
