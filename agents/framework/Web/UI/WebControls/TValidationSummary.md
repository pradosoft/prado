# TValidationSummary

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TValidationSummary](./TValidationSummary.md)

**Location:** `framework/Web/UI/WebControls/TValidationSummary.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TValidationSummary displays a summary of validation errors on a Web page. It collects error messages from all failed validators and displays them in a configurable format (list, bullet list, or paragraph). Can show inline and/or in a message box.

## Key Properties/Methods

- `getDisplay()` / `setDisplay(TValidationSummaryDisplayStyle)` - Display style (Fixed, Dynamic, None)
- `getHeaderText()` / `setHeaderText(string)` - Header text
- `getDisplayMode()` / `setDisplayMode(TValidationSummaryDisplayMode)` - Message format
- `getShowMessageBox()` / `setShowMessageBox(bool)` - Show in message box
- `getShowSummary()` / `setShowSummary(bool)` - Show inline summary
- `getEnableClientScript()` / `setEnableClientScript(bool)` - Enable client-side updates
- `getValidationGroup()` / `setValidationGroup(string)` - Validator group to summarize
- `getClientSide()` - Client-side event options
- `getErrorMessages()` - Collects error messages from validators

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TValidationSummaryDisplayStyle](./TValidationSummaryDisplayStyle.md)
