# TEmptyControlCollection

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TEmptyControlCollection](./TEmptyControlCollection.md)

**Location:** `framework/Web/UI/TEmptyControlCollection.php`
**Namespace:** `Prado\Web\UI`

## Overview

TEmptyControlCollection implements a control list that prohibits adding controls. It extends [TControlCollection](./TControlCollection.md) and is useful for controls that do not allow child controls. String items are silently ignored (used for property tags), but any other item type throws an exception.

## Key Properties/Methods

- `insertAt($index, $item)` - Ignores string items, throws exception for non-string/non-control items

## See Also

- [TControlCollection](./TControlCollection.md)
- [TControl](./TControl.md)

(End of file - total 17 lines)
