# TClientSideOptions

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TClientSideOptions](./TClientSideOptions.md)

**Location:** `framework/Web/UI/TClientSideOptions.php`
**Namespace:** `Prado\Web\UI`

## Overview

TClientSideOptions manages client-side options for components with common JavaScript behaviors and events. It provides a TMap-based options storage and helper methods for setting JavaScript function handlers. Options are commonly used between ActiveControls and validators.

## Key Properties/Methods

- `Options` - Returns the TMap containing all options
- `setOption($name, $value)` - Sets an option value
- `getOption($name)` - Gets an option value
- `setFunction($name, $code)` - Sets an option as a JavaScript function, wrapping code if needed
- `ensureFunction($javascript)` - Ensures JavaScript code is wrapped in a function block

## See Also

- [TActiveControl](./ActiveControls/TActiveControl.md)
- [TBaseValidator](./WebControls/TBaseValidator.md)
- [TMap](../Collections/TMap.md)

(End of file - total 22 lines)
