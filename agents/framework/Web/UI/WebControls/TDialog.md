# Web/UI/WebControls/TDialog

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDialog`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDialog.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TWebControl](./TWebControl.md)

## Overview
Renders the HTML5 `<dialog>` element. [TButton](./TButton.md) invokers (`CommandFor`, `Command`) open and close it in the browser without script, inside the page form, so its fields post back with the page. [TActiveDialog](../ActiveControls/TActiveDialog.md) raises server events when it opens or closes.

## Key Properties

| Property | Default | Renders |
|---|---|---|
| `Open` | `false` | the dialog shown on load |
| `Modal` | `false` | an Open dialog shown modally |
| `ClosedBy` | `Auto` | `closedby` ([TDialogClosedBy](./TDialogClosedBy.md)); `Any` closes on a click outside (light dismiss) or Esc |
| `HeaderText` | `''` | an `<h2>` heading (`HeaderLevel` 1–6) in the header block; it names the dialog through `aria-labelledby` unless `Attributes.aria-labelledby` is set. `Encode` HTML-encodes it |
| `ShowCloseButton` | `false` | a `<button commandfor command="close">` in the header block; `CloseButtonText` (localized "Close") is its `aria-label` |
| `CssUrl` | `default` | the stylesheet of the header block (`assets/dialog.css`), registered only when the block renders; `''` registers none |

| Open | Modal | Rendering |
|---|---|---|
| false | any | no `open`; closed |
| true | false | `open`; non-modal |
| true | true | the client ID and an end script (`prado:dialog:<ClientID>`) that calls `showModal()`; without JavaScript the dialog stays closed |

The `open` attribute always shows a dialog non-modally, so a Modal dialog renders without it. `registerShowModalScript()` registers the end script; TActiveDialog overrides it and passes `ShowModal` to its client control instead, so the open is not echoed to the server.

### Header Block

HeaderText or ShowCloseButton renders a block before the body, and the dialog renders its client ID:

```html
<div class="dialog-header">
	<h2 id="{ClientID}_header" class="dialog-title">Edit name</h2>
	<button type="button" class="dialog-close" commandfor="{ClientID}" command="close" aria-label="Close">&times;</button>
</div>
```

The close button closes the dialog without script, with an empty `returnValue`. `getHeaderClientID()` is the heading ID. The block renders when the dialog renders, so a callback change of HeaderText reaches the browser only when the dialog is re-rendered.

## Template Usage

```xml
<com:TButton Text="Edit" CommandFor="EditDialog" Command="show-modal" />
<com:TDialog ID="EditDialog" Modal="true" ClosedBy="Any" HeaderText="Edit name" ShowCloseButton="true">
	<com:TLabel ForControl="Name" Text="Name" />
	<com:TTextBox ID="Name" />
	<com:TButton Text="Save" OnClick="saveClicked" />
	<com:TButton Text="Cancel" CommandFor="EditDialog" Command="close" Attributes.value="cancel" />
</com:TDialog>
```

`saveClicked` sets `EditDialog.Open` to true when the input fails, so the dialog reopens modally after the postback, and to false when it succeeds.

## Accessibility
- The browser gives `<dialog>` the `dialog` role; `HeaderText` names it, or `aria-labelledby` on a heading in the body.
- The close button's name is `CloseButtonText`; its visible `×` is not read.
- A modal dialog makes the rest of the page inert and takes the focus; closing returns the focus to the element focused before it opened, such as the invoker.
- A modal dialog closes on Esc by default; `ClosedBy="None"` removes Esc, so give it a close button.

## Gotchas
- `Open` is kept in view state. Opening or closing the dialog in the browser does not change it; a postback handler that opened the dialog sets `Open` to false to close it.
- A `<form method="dialog">` cannot sit inside the page form; put it in a [TTeleport](./TTeleport.md).

## Tests
- `tests/unit/Web/UI/WebControls/TDialogTest.php`
- `tests/playwright/web/DialogInvokerTestCase.spec.js` with `tests/harness/web/protected/pages/DialogInvokerTest.page`

## See Also
- [TButton](./TButton.md): `CommandFor`, `Command`
- [TActiveDialog](../ActiveControls/TActiveDialog.md)
- [TTeleport](./TTeleport.md)
- [TDrawer](./TDrawer.md): a dialog at a page edge

**@since 4.4.0**
