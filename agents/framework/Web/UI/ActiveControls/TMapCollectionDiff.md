# Web/UI/ActiveControls/TMapCollectionDiff

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TMapCollectionDiff`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TMapCollectionDiff.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Calculates changes to attribute collections by comparing old and new TMap collections. Returns the difference as an array of the added and changed attribute name-value pairs, and each removed key with a null value; a null map has no items.

## Key Properties/Methods

- `getDifference()` - Returns the new and changed items, and each removed key with a null value, or the null sentinel when nothing changed. Items compare as strings; array items (jQuery UI options) compare by their elements. `TCallbackPageStateTracker::updateAttributes()` removes the client attribute of a null value (4.4.0)

## See Also

- [TViewStateDiff](./TViewStateDiff.md), [TCallbackPageStateTracker](./TCallbackPageStateTracker.md)
