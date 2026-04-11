# Web/UI/ActiveControls/TActiveFileUpload

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveFileUpload`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveFileUpload.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Async file upload using hidden iframe. Does postback in hidden iframe followed by callback to raise OnFileUpload event. Displays status icons (spinning, checkmark, error) during upload. Supports HTML5 multiple file uploads. Requires application cache or security manager.

## Key Properties/Methods

- `getTempPath()` / `setTempPath($value)` - Temporary file storage path
- `getAutoPostBack()` / `setAutoPostBack($value)` - Auto callback on file selection
- `getCallbackJavascript()` - JavaScript to trigger callback manually
- `onFileUpload($param)` - Event raised when file upload completes
- `getFiles()` - Gets uploaded file items
- `getBusyImage()`, `getSuccessImage()`, `getErrorImage()` - Status indicator images
- `getClientClassName()` - Returns `Prado.WebUI.TActiveFileUpload`

## See Also

- `TFileUpload`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TActiveFileUploadItem](./TActiveFileUploadItem.md)
