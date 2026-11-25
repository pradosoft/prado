# Web/UI/WebControls/TWizardNavigationButtonStyle

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TWizardNavigationButtonStyle`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TWizardNavigationButtonStyle.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TWizardNavigationButtonStyle defines the style applied to wizard navigation buttons. It specifies button type (Button, Image, or Link), image URL for image buttons, and button text/caption.

## Key Properties/Methods

- `getImageUrl()` / `setImageUrl(string)` - Image URL for image buttons
- `getButtonText()` / `setButtonText(string)` - Button caption text
- `getButtonType()` / `setButtonType(TWizardNavigationButtonType)` - Type of button
- `apply($button)` - Applies style to a button control; the button keeps its own text, image URL, and style fields
- `copyFrom($style)` - The source's set fields overwrite this style's, including `ImageUrl`, `ButtonText`, and `ButtonType`, as in `TStyle`
- `mergeWith($style)` - This style's set fields win; the base fills only the unset ones, as in `TStyle`

## See Also

- [TWizardNavigationButtonType](./TWizardNavigationButtonType.md)
- [TWizard](./TWizard.md)
