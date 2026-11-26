# Web/UI/ActiveControls/TActiveLabel

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveLabel`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveLabel.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TLabel. Text and ForControl properties can be updated during callback and the changes are reflected on the client side.

## Key Properties/Methods

- `setText($value)` - Sets text with client-side update, HTML-encoded when `Encode` is true (4.4.0)
- `setForControl($value)` - Sets associated control ID with client-side update. A change between an empty and a non-empty ID changes the tag (`<span>` or `<label>`), so the callback replaces the element; a change between two IDs sets `for`. An ID that locates no control throws `TInvalidDataValueException` in a callback
- `getActiveControl()` - Returns [TBaseActiveControl](./TBaseActiveControl.md) options

## See Also

- `TLabel`, [IActiveControl](./IActiveControl.md)
