# Web/UI/ActiveControls/TActiveListItemCollection

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveListItemCollection`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveListItemCollection.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Allows [TActiveDropDownList](./TActiveDropDownList.md) and [TActiveListBox](./TActiveListBox.md) to add new options during callback response. Tracks changes after OnLoad event and updates client-side list items when modified.

## Key Properties/Methods

- `insertAt($index, $value)` - Inserts item with client-side update
- `removeAt($index)` - Removes item with client-side update
- `getListHasChanged()` - Returns true if items changed after OnLoad
- `updateClientSide()` - Updates client-side list options
- `canUpdateClientSide()` - Checks if updates are allowed

## See Also

- `TListItemCollection`, [TActiveDropDownList](./TActiveDropDownList.md), [TActiveListBox](./TActiveListBox.md)
