# Web/UI/ActiveControls/TActiveDialog

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveDialog`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveDialog.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Extends:** [TDialog](../WebControls/TDialog.md)
**Implements:** `IActiveControl`, `ICallbackEventHandler`

## Overview
A `<dialog>` that raises `OnOpen` and `OnClose` by callback when it opens or closes in the browser, and opens or closes in the browser when `Open` changes in a callback. Client class: `Prado.WebUI.TActiveDialog` in `activecontrols3.js`.

## Events and Properties

| Member | Description |
|---|---|
| `OnOpen` | Raised after `Open` is set to true and `ReturnValue` to '' |
| `OnClose` | Raised after `Open` is set to false and `ReturnValue` to the dialog's `returnValue` |
| `ReturnValue` | Read only: the `returnValue` of the last close; '' while open |
| `Modal` | Inherited; an Open dialog shows modally on page load (client option `ShowModal`) and when `Open` is set in a callback; set Modal before Open |

## Callback Parameter

| Client event | `CallbackParameter` |
|---|---|
| `open` attribute added (`show()`, `showModal()`, a `show-modal` invoker) | `'open'` |
| `close` event | `['close', returnValue]` (a plain `'close'` is accepted and gives '') |

The client resets `returnValue` to '' when the dialog opens, so a close by Esc or light dismiss reports '' rather than an earlier button's value. A server `setOpen()` calls `Prado.WebUI.TActiveDialog.setOpen(id, open, modal)`, which suppresses the echo of its own open or close.

## Template Usage

```xml
<com:TButton Text="Delete" CommandFor="Confirm" Command="show-modal" />
<com:TActiveDialog ID="Confirm" OnClose="confirmClosed" Attributes.aria-labelledby="ConfirmTitle">
	<h2 id="ConfirmTitle">Delete the file?</h2>
	<com:TButton Text="Delete" CommandFor="Confirm" Command="close" Attributes.value="delete" />
	<com:TButton Text="Cancel" CommandFor="Confirm" Command="close" Attributes.value="cancel" />
</com:TActiveDialog>
```

`confirmClosed` deletes when `$sender->ReturnValue === 'delete'`.

## Tests
- `tests/unit/Web/UI/ActiveControls/TActiveDialogTest.php`
- `tests/js/activecontrols/activecontrols.test.js`
- `tests/playwright/active-controls/ActiveDialogTestCase.spec.js` with `tests/harness/active-controls/protected/pages/ActiveDialogTest.page`

## See Also
- [TDialog](../WebControls/TDialog.md)
- [TButton](../WebControls/TButton.md): `CommandFor`, `Command`

**@since 4.4.0**
