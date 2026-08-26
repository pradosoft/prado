# Web/UI/WebControls/TLinkButton

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TLinkButton`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TLinkButton.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TLinkButton creates a hyperlink-style button that posts back to the page. Can be a submit button or command button with parameters. It renders an `<a>` tag whose href is a no-op javascript url, so the postback depends on javascript.

## Key Properties/Methods

- `Text` - Button caption; the body content is rendered when `Text` is empty
- `CommandName` - Command name for OnCommand event
- `CommandParameter` - Parameter for command
- `CausesValidation` - Whether button triggers validation, default true
- `ValidationGroup` - Validation group to trigger
- `EnableClientScript` - Whether to render the href and postback javascript, default true
- `IsDefaultButton` - Set by a [TPanel](./TPanel.md) to make this the panel's default button
- `onClick($param)` - Click event
- `onCommand($param)` - Command event (bubbles)
- `raisePostBackEvent($param)` - Validates, then raises OnClick and OnCommand

## See Also

- [TButton](./TButton.md)
- [TWebControl](./TWebControl.md)
