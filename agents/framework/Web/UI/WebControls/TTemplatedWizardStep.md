# TTemplatedWizardStep

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TTemplatedWizardStep](./TTemplatedWizardStep.md)

**Location:** `framework/Web/UI/WebControls/TTemplatedWizardStep.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TTemplatedWizardStep represents a wizard step whose content and navigation can be customized using templates. Extends TWizardStep and implements INamingContainer.

## Key Properties/Methods

- `getContentTemplate()` / `setContentTemplate($value)` - Template for step content
- `getNavigationTemplate()` / `setNavigationTemplate($value)` - Template for navigation
- `getNavigationContainer()` - Returns the navigation container control
- `createChildControls()` - Creates child controls from content template
- `instantiateNavigationTemplate()` - Creates navigation template

## See Also

- [TWizardStep](./TWizardStep.md)
- [TWizard](./TWizard.md)
