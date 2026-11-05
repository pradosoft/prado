# Web/UI/ActiveControls/TActiveHyperLink

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveHyperLink`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveHyperLink.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to THyperLink. Text, ImageUrl, NavigateUrl, and Target properties can be updated during callback and reflected on the client.

## Key Properties/Methods

- `setText($value)` - Sets text with client-side update; the text is HTML-encoded, as `THyperLink` renders it (4.4.0)
- `setImageUrl($value)` - Sets image URL with client-side update
- `setNavigateUrl($value)` - Sets href with client-side update
- `setTarget($value)` - Sets target window/frame with client-side update
- `getActiveControl()` - Returns [TBaseActiveControl](./TBaseActiveControl.md) options

## See Also

- `THyperLink`, [IActiveControl](./IActiveControl.md)
