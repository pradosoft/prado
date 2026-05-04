# Web/UI/JuiControls/TJuiDialog

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [JuiControls](./INDEX.md) / **`TJuiDialog`**

## Class Info
**Location:** `framework/Web/UI/JuiControls/TJuiDialog.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview
Modal/non-modal dialog widget based on jQuery UI Dialog. Extends [TActivePanel](../ActiveControls/TActivePanel.md). Content is rendered server-side as child controls. Supports buttons via [TJuiDialogButton](TJuiDialogButton.md) children.

## Key Properties/Methods

- `open()` - Opens the dialog client-side
- `close()` - Closes the dialog client-side
- `getOptions()` - Dialog options (modal, title, buttons, etc.)
- `getValidOptions()` - Valid options: appendTo, autoOpen, buttons, closeOnEscape, dialogClass, draggable, height, hide, minHeight, minWidth, maxHeight, maxWidth, modal, position, resizable, show, title, width
- `getValidEvents()` - Events: beforeClose, close, create, drag, dragStart, dragStop, focus, open, resize, resizeStart, resizeStop
- `setGroupingText($value)` - Not supported (throws TNotSupportedException)
- `render($writer)` - Handles client-side updates without destroying dialog

## See Also

- [TJuiDialogButton](TJuiDialogButton.md)
