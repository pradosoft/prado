# TBaseActiveControl

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TBaseActiveControl](./TBaseActiveControl.md)

**Location:** `framework/Web/UI/ActiveControls/TBaseActiveControl.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Basic properties for every active control. Provides options management and determines whether the active control can update client-side contents during callback response.

## Key Properties/Methods

- `setOption($name, $value, $default)` - Set named option
- `getOption($name, $default)` - Get named option
- `getEnableUpdate()` / `setEnableUpdate($value)` - Allow fine-grain callback updates
- `canUpdateClientSide($bDontRequireVisibility)` - Check if client updates are allowed

## See Also

- [TBaseActiveCallbackControl](./TBaseActiveCallbackControl.md), [TActiveControlAdapter](./TActiveControlAdapter.md)
