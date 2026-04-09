# TViewStateDiff

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TViewStateDiff](./TViewStateDiff.md)

**Location:** `framework/Web/UI/ActiveControls/TViewStateDiff.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Abstract base class for calculating viewstate changes during callback requests. Tracks the difference between old and new viewstate values to optimize client-side updates by only sending changed properties.

## Key Properties/Methods

- `$_new` - Updated viewstate value
- `$_old` - Viewstate value at request start
- `$_null` - Null value representation
- `getDifference()` - Abstract method returning changes or null object if no difference

## See Also

- [TScalarDiff](./TScalarDiff.md), [TStyleDiff](./TStyleDiff.md), [TMapCollectionDiff](./TMapCollectionDiff.md)
