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
- `getCausesValidation()` / `setCausesValidation($value)` - Validation integration on/off, default true (@since 4.4.0)
- `getValidationGroup()` / `setValidationGroup($value)` - Group the page validates during the upload callback (@since 4.4.0)
- `getClientClassName()` - Returns `Prado.WebUI.TActiveFileUpload`

## Validation Integration (@since 4.4.0)

With `CausesValidation` (default true) and a [TFileValidator](../WebControls/TFileValidator.md)/[TImageValidator](../WebControls/TImageValidator.md) attached to the control:

- **Client:** `fileChanged()` runs `manager.validateControl(inputID)` before the iframe submit; an invalid selection skips the upload and the validators display their messages. Guarded — pages without the validator script or a validation manager upload as before.
- **Server:** `raiseCallbackEvent()` calls `$page->validate(ValidationGroup)` after `loadPostData()` and before raising `OnFileUpload`. The handler checks `$this->getPage()->getIsValid()` (or the upload's `IsValid`) before `saveAs()`. The temp files persist in `TempPath` during the callback, so `finfo`/`getimagesize` sniffing works.
- **Timing trap:** validate during the upload callback, never on a later postback — a successful upload clears the input and `onUnload` deletes the temp files, so later validators see an empty selection and pass vacuously.
- Give the upload and its validators a dedicated `ValidationGroup` so the upload callback does not validate unrelated page controls.
- The status icons reflect transfer status only; server-side validation feedback is the `OnFileUpload` handler's responsibility (e.g. via an active label).

Functional test: `tests/playwright/active-controls/ActiveFileUploadValidatorTestCase.spec.js` + `TActiveFileUploadValidatorTest.page`.

## See Also

- `TFileUpload`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TActiveFileUploadItem](./TActiveFileUploadItem.md)
