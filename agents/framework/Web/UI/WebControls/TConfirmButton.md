# Web/UI/WebControls/TConfirmButton

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TConfirmButton`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TConfirmButton.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TButton](./TButton.md)

## Overview
TConfirmButton (@since 4.4.0, #1401) asks for confirmation in a modal `<dialog role="alertdialog">` before it posts back. It replaces `onclick="return confirm('...')"` and needs no script. With `ConfirmText` empty, or the button disabled, it renders as a TButton.

## Template Usage

```xml
<com:TConfirmButton Text="Delete" ConfirmText="Delete this record?"
	ConfirmButtonText="Delete" OnClick="deleteRecord" />
```

## Key Properties

| Property | Default | Description |
|---|---|---|
| `ConfirmText` | `''` | The message, the dialog's `aria-describedby`; `''` renders a TButton |
| `ConfirmHeaderText` | localized "Confirm" | The `<h2>` heading, the dialog's `aria-labelledby` |
| `ConfirmButtonText` | localized "OK" | The text of the OK button, which posts back |
| `CancelButtonText` | localized "Cancel" | The text of the Cancel button, which takes the focus |
| `CssUrl` | `default` | `'default'` publishes the [TDialog](./TDialog.md) stylesheet `dialog.css`; `''` registers none |
| `Encode` | `false` | HTML-encodes `ConfirmText` and `ConfirmHeaderText`; the button texts are always encoded |

## Rendering

```html
<button id="{ClientID}" type="button" aria-haspopup="dialog" commandfor="{ClientID}_confirm" command="show-modal">Delete</button>
<!-- after </form>: -->
<dialog id="{ClientID}_confirm" role="alertdialog" class="prado-confirm" aria-labelledby="…_header" aria-describedby="…_message">
	<div class="dialog-header"><h2 id="{ClientID}_confirm_header" class="dialog-title">Confirm</h2></div>
	<p id="{ClientID}_confirm_message">Delete this record?</p>
	<div class="dialog-buttons">
		<button type="button" commandfor="{ClientID}_confirm" command="close" autofocus>Cancel</button>
		<button type="submit" id="{ClientID}_confirm_ok" name="{UniqueID}" value="{Text}" form="{FormClientID}">OK</button>
	</div>
</dialog>
```

- The visible button keeps the control's ClientID, `CssClass`, `Style`, and other attributes. `ButtonTag` and `ButtonType` do not apply.
- Inside the page form, the dialog goes to `TForm::registerTeleportContent()` and renders after `</form>`, so it does not break the paragraph or table around the button; the OK button's `form` attribute names the page form. In a callback or outside a form, the dialog renders in place.
- The OK button posts `name="{UniqueID}"`, so the page raises the TConfirmButton's `OnClick` and `OnCommand`. It renders `formnovalidate` when `CausesValidation` is false, and the `FormAction`, `FormEncType`, and `FormTarget` overrides.
- With client validation, `Prado.WebUI.TConfirmButton` (in `controls/controls.js`, a `TButton` subclass) handles the OK button; when validation fails, it closes the dialog so the messages show.

## Accessibility
- The dialog is a modal `alertdialog`, named by its heading and described by the message. The page behind it is inert.
- Cancel, the least destructive action, takes the focus on opening. Esc and Cancel close the dialog and return the focus to the button.

## Tests
- `tests/unit/Web/UI/WebControls/TConfirmButtonTest.php`
- `tests/js/controls/controls.test.js` (`TConfirmButton`)
- `tests/playwright/web/ConfirmButtonTestCase.spec.js` with `tests/harness/web/protected/pages/ConfirmButtonTest.page`

## See Also
- [TButton](./TButton.md)
- [TDialog](./TDialog.md)
- [TTeleport](./TTeleport.md)

**@since 4.4.0**
