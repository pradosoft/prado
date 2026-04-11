# Web/UI/WebControls/TWebControlDecorator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TWebControlDecorator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TWebControlDecorator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TWebControlDecorator customizes TWebControl rendering by adding HTML before and after both the open and close tags. Supports both text and template-based decoration for theming.

## Key Properties/Methods

- `getPreTagText()` / `setPreTagText($value)` - Text before opening tag
- `getPreContentsText()` / `setPreContentsText($value)` - Text after opening tag
- `getPostContentsText()` / `setPostContentsText($value)` - Text before closing tag
- `getPostTagText()` / `setPostTagText($value)` - Text after closing tag
- `getPreTagTemplate()` / `setPreTagTemplate($value)` - Template before opening tag
- `getPreContentsTemplate()` / `setPreContentsTemplate($value)` - Template after opening tag
- `getPostContentsTemplate()` / `setPostContentsTemplate($value)` - Template before closing tag
- `getPostTagTemplate()` / `setPostTagTemplate($value)` - Template after closing tag
- `getUseState()` / `setUseState($value)` - Whether templates need state
- `instantiate($outercontrol)` - Framework call to setup template decoration

## See Also

- [TWebControl](./TWebControl.md)
