# Web/UI/WebControls/TImageButton

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TImageButton`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TImageButton.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TImageButton creates an image button on a Web page for submitting data. It can be a submit button or a command button with command name and parameter. Click coordinates are captured and available via OnClick event parameter.

It extends [TImage](./TImage.md) and renders `<input type="image">`. The browser posts the click position as `<uniqueID>_x` and `<uniqueID>_y`, which have no direct mapping to the control name, so the control registers itself with `TPage::registerRequiresPostData()` during `onPreRender()`.

## Key Properties/Methods

- `getCommandName()` / `setCommandName()` - Gets or sets command name for OnCommand event
- `getCommandParameter()` / `setCommandParameter()` - Gets or sets command parameter
- `getCausesValidation()` / `setCausesValidation()` - Gets or sets whether button triggers validation
- `getValidationGroup()` / `setValidationGroup()` - Gets or sets validation group
- `getText()` / `setText()` - Gets or sets button caption (used as alt text)
- `getIsDefaultButton()` / `setIsDefaultButton()` - Gets or sets whether this is a default button
- `getEnableClientScript()` / `setEnableClientScript()` - Gets or sets whether to render JavaScript
- `onClick($param)` - Raises OnClick event with a `TImageClickEventParameter` holding the click coordinates
- `onCommand($param)` - Raises OnCommand event with command name and parameter, and bubbles it
- `raisePostBackEvent($param)` - Validates, then raises OnClick and OnCommand; `$param` is unused, the coordinates come from `loadPostData()`
- `loadPostData($key, $values)` - Reads the click coordinates and makes the button the page's postback event target; always returns false
- `getDataChanged()` - True when the button was clicked in this postback
- `needPostBackScript()` - Javascript is rendered only for validation or for a panel default button; a plain image button submits natively

## See Also

- [TImage](./TImage.md)
- [TImageClickEventParameter](./TImageClickEventParameter.md)
- [IPostBackEventHandler](../IPostBackEventHandler.md)
