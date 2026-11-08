# Web/UI/WebControls/TConfirmSubmit

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TConfirmSubmit`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TConfirmSubmit.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [THtmlElement](./THtmlElement.md)
**Uses:** [TConfirmDialogTrait](./TConfirmDialogTrait.md)

## Overview
TConfirmSubmit (@since 4.4.0, #1401) wraps controls and asks for confirmation before they act. The client control `Prado.WebUI.TConfirmSubmit` (`controls/confirmsubmit.js`, package `confirmsubmit`) stops the user action in a capture listener on the wrapper and opens the dialog. OK sends the event again, so the control's own handlers run. It needs no knowledge of `Prado.PostBack` or callbacks.

Without script the controls act with no confirmation. [TConfirmButton](./TConfirmButton.md) confirms without script.

## Template Usage

```xml
<com:TConfirmSubmit ConfirmText="Delete this record?" ConfirmButtonText="Delete">
	<com:TLinkButton Text="Delete" OnClick="deleteRecord" />
</com:TConfirmSubmit>
```

## What Asks

| User action | Controls | On Cancel |
|---|---|---|
| click | `a[href]`, `area[href]`, `button`, and `input` of type `submit`, `image`, `button`, `reset` | the click has no effect |
| click | a checkbox or radio button with AutoPostBack | the state stays, because the click was cancelled |
| change | a `select`, `textarea`, or text `input` with AutoPostBack | the value returns to the one recorded on focus |

Active controls are the same as their postback counterparts. Disabled controls, controls without AutoPostBack, and controls outside the wrapper do not ask.

## Rendering

- A `<span>` by default; `TagName` changes it. The ID always renders. With `ConfirmText` empty or `Enabled` false, only the wrapper renders.
- The client control and its `AutoPostBack` option register at render time, so children from a data binding count. `findAutoPostBackClientIDs()` walks the visible descendants with a true `getAutoPostBack()`.
- The dialog renders after `</form>`; the OK button is a `type="button"` that the client control handles.

## Behavior

- The resumed click copies the position, button, and modifier keys of the original.
- The action runs after the dialog closes, so the page is not inert and the focus has returned to the control. Validation runs then: a failure shows its message with no postback.
- Enter in a text box with AutoPostBack has its default action cancelled. It opens the dialog during `keydown`, and the rest of the key press would otherwise activate the focused Cancel button and submit the form.

## Gotchas
- Script that sends a jQuery `.trigger()` straight to a control's handlers bypasses the capture listener. User actions do not.
- A click on a `TImageButton` by keyboard posts 0,0, as without the wrapper.

## Tests
- `tests/unit/Web/UI/WebControls/TConfirmSubmitTest.php`
- `tests/js/controls/confirmsubmit.test.js`
- `tests/playwright/web/ConfirmSubmitTestCase.spec.js` with `tests/harness/web/protected/pages/ConfirmSubmitTest.page`

## See Also
- [TConfirmButton](./TConfirmButton.md)
- [TDialog](./TDialog.md)
- [TTeleport](./TTeleport.md)

**@since 4.4.0**
