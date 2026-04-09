# TActiveHtmlArea

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveHtmlArea](./TActiveHtmlArea.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveHtmlArea.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to THtmlArea (TinyMCE 3/4). Supports callback handling and content updates during callback. Text property can be updated client-side when EnableVisualEdit is enabled.

## Key Properties/Methods

- `setText($value)` - Sets content with TinyMCE update if visual edit enabled
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveHtmlArea`

## See Also

- `THtmlArea`, [ICallbackEventHandler](./ICallbackEventHandler.md)
