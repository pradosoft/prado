# Web/UI/WebControls/TWizardNavigationTemplate

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TWizardNavigationTemplate`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TWizardNavigationTemplate.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TWizardNavigationTemplate is the base class for wizard navigation templates. It implements ITemplate and provides a method to create navigation buttons (TButton, TLinkButton, or TImageButton) based on button style settings.

## Key Properties/Methods

- `getWizard()` - Gets the wizard owning this template
- `instantiateIn($parent)` - Creates the template controls
- `createNavigationButton($buttonStyle, $causesValidation, $commandName)` - Creates appropriate button type

## See Also

- [TWizardStartNavigationTemplate](./TWizardStartNavigationTemplate.md)
- [ITemplate](./ITemplate.md)
