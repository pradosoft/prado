# TActiveHtmlArea5

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveHtmlArea5](./TActiveHtmlArea5.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveHtmlArea5.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to THtmlArea5 (TinyMCE 5+). Supports callback handling and content updates during callback. Text property can be updated client-side when EnableVisualEdit is enabled.

## Key Properties/Methods

- `setText($value)` - Sets content with TinyMCE update if visual edit enabled
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveHtmlArea5`

## See Also

- `THtmlArea5`, [ICallbackEventHandler](./ICallbackEventHandler.md)
