# Web/UI/WebControls/TConfirmDialogTrait

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TConfirmDialogTrait`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TConfirmDialogTrait.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Used by:** [TConfirmButton](./TConfirmButton.md), [TConfirmSubmit](./TConfirmSubmit.md)

## Overview
The confirmation dialog of both controls: the properties `ConfirmText`, `ConfirmHeaderText`, `ConfirmButtonText`, `CancelButtonText`, `CssUrl`, and `Encode` (through [TEncodeTrait](./TEncodeTrait.md)), and the rendering of a modal `<dialog role="alertdialog">` with a heading, the message, and a Cancel button that takes the focus.

| Member | Purpose |
|---|---|
| `getDialogClientID()` | `{ClientID}_confirm` |
| `getConfirmButtonClientID()` | `{ClientID}_confirm_ok` |
| `registerDialogStyleSheet()` | Registers `CssUrl`; `'default'` publishes the TDialog `dialog.css` |
| `renderConfirmDialog($writer)` | Renders the dialog after `</form>` through `TForm::registerTeleportContent()`, or in place outside a form and in a callback |
| `renderConfirmButton($writer, $formID)` | Abstract: the OK button, which differs by control |

## Gotchas
- A callback that re-renders the control renders the dialog in place, while the dialog of the page load stays after `</form>`. The in-place one comes first in the document, so the IDs resolve to it.

**@since 4.4.0**
