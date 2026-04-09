# IJuiOptions

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [JuiControls](./INDEX.md) > [IJuiOptions](./IJuiOptions.md)

**Location:** `framework/Web/UI/JuiControls/IJuiOptions.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview

Interface that must be implemented by controls using TJuiControlOptions. Defines the contract for jQuery UI widget wrappers to provide widget configuration and validation.

## Key Properties/Methods

- `getWidget()` - Returns the name of the jQueryUI widget method
- `getWidgetID()` - Returns the client ID of the jQueryUI widget element
- `getOptions()` - Returns TJuiControlOptions object containing defined JavaScript options
- `getValidOptions()` - Returns array of valid JavaScript options
- `getValidEvents()` - Returns array of valid JavaScript events

## See Also

- [TJuiControlOptions](TJuiControlOptions.md)
- [TJuiControlAdapter](TJuiControlAdapter.md)
