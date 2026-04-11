# Web/UI/ActiveControls/TActivePager

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActivePager`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActivePager.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TPager that uses callbacks instead of postbacks for page navigation. Raises OnCallback event after OnPageIndexChanged. Creates [TActiveDropDownList](./TActiveDropDownList.md) for dropdown mode and [TActiveLinkButton](./TActiveLinkButton.md)/[TActiveButton](./TActiveButton.md)/[TActiveImageButton](./TActiveImageButton.md) for button modes.

## Key Properties/Methods

- `buildListPager()` - Creates active dropdown list for page selection
- `createPagerButton(...)` - Creates appropriate active button type
- `handleCallback($sender, $param)` - Event handler for callback buttons
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested

## See Also

- `TPager`, [TActiveDataGrid](./TActiveDataGrid.md), [TActiveRepeater](./TActiveRepeater.md)
