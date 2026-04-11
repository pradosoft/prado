# Web/UI/TCompositeControl

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TCompositeControl`**

## Class Info
**Location:** `framework/Web/UI/TCompositeControl.php`
**Namespace:** `Prado\Web\UI`

## Overview
TCompositeControl is the base class for controls composed of other controls. It implements INamingContainer and ensures child controls are created before the parent control proceeds with initialization. This is the base class for controls like TPanel, TRepeater, and custom composite controls.

## Key Properties/Methods

- `initRecursive($namingContainer)` - Ensures child controls are created before parent initialization

## See Also

- [TControl](./TControl.md)
- [INamingContainer](./INamingContainer.md)

(End of file - total 17 lines)
