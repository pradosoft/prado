# Web/UI/WebControls/TImageButton

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TImageButton`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TImageButton.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TImageButton creates an image button on a Web page for submitting data. It can be a submit button or a command button with command name and parameter. Click coordinates are captured and available via OnClick event parameter.

## Key Properties/Methods

- `getCommandName()` / `setCommandName()` - Gets or sets command name for OnCommand event
- `getCommandParameter()` / `setCommandParameter()` - Gets or sets command parameter
- `getCausesValidation()` / `setCausesValidation()` - Gets or sets whether button triggers validation
- `getValidationGroup()` / `setValidationGroup()` - Gets or sets validation group
- `getText()` / `setText()` - Gets or sets button caption (used as alt text)
- `getIsDefaultButton()` / `setIsDefaultButton()` - Gets or sets whether this is a default button
- `getEnableClientScript()` / `setEnableClientScript()` - Gets or sets whether to render JavaScript
- `onClick()` - Raises OnClick event with image click coordinates
- `onCommand()` - Raises OnCommand event with command name and parameter
- `raisePostBackEvent()` - Raises postback event, triggers validation if enabled

## See Also

- [TImage](./TImage.md)
- [TImageClickEventParameter](./TImageClickEventParameter.md)
- [IPostBackEventHandler](./IPostBackEventHandler.md)
