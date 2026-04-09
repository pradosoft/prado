# TWizardStep

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TWizardStep](./TWizardStep.md)

**Location:** `framework/Web/UI/WebControls/TWizardStep.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TWizardStep represents a step in a TWizard. It extends TView and provides properties for title, step type, and allow return functionality.

## Key Properties/Methods

- `getWizard()` - Returns the wizard owning this step
- `getTitle()` / `setTitle($value)` - Step title for sidebar
- `getAllowReturn()` / `setAllowReturn($value)` - Whether step can be revisited
- `getStepType()` / `setStepType($type)` - Wizard step type (Auto, Start, Step, Finish, Complete)

## See Also

- [TWizard](./TWizard.md)
- [TView](./TView.md)
- [TWizardStepType](./TWizardStepType.md)
