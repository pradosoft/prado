# TControlCollection

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TControlCollection](./TControlCollection.md)

**Location:** `framework/Web/UI/TControlCollection.php`
**Namespace:** `Prado\Web\UI`

## Overview

TControlCollection implements a collection that enables controls to maintain a list of their child controls. It extends [TList](../Collections/TList.md) and performs additional operations when child controls are added or removed, including calling `addedControl()` and `removedControl()` on the owner control.

## Key Properties/Methods

- `Owner` - The control that owns this collection
- `insertAt($index, $item)` - Inserts a TControl or string, calling `addedControl()` for TControl instances
- `removeAt($index)` - Removes item and calls `removedControl()` if item is a TControl
- `clear()` - Clears the collection and invokes `clearNamingContainer()` if owner is INamingContainer

## See Also

- [TControl](./TControl.md)
- [TEmptyControlCollection](./TEmptyControlCollection.md)
- [TList](../Collections/TList.md)

(End of file - total 21 lines)
