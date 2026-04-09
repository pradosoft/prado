# TActiveDatePicker

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveDatePicker](./TActiveDatePicker.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveDatePicker.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TDatePicker. Triggers callback when date selection changes. Text property can be updated during callback. Supports AutoPostBack and validation options.

## Key Properties/Methods

- `getAutoPostBack()` / `setAutoPostBack($value)` - Auto callback on date change
- `setText($value)` - Sets date text with client-side update
- `getDatePickerOptions()` - Returns JavaScript options for the date picker
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveDatePicker`

## See Also

- `TDatePicker`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TActiveDatePickerClientScript](./TActiveDatePickerClientScript.md)
