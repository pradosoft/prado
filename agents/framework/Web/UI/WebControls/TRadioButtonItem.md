# TRadioButtonItem

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TRadioButtonItem](./TRadioButtonItem.md)

**Location:** `framework/Web/UI/WebControls/TRadioButtonItem.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TRadioButtonItem extends TRadioButton and is used within TRadioButtonList. It overrides the client implementation to avoid emitting JavaScript, as the parent list handles client-side behavior.

## Key Properties/Methods

- Extends TRadioButton
- `renderClientControlScript($writer)` - Overridden to emit no JavaScript

## See Also

- [TRadioButton](./TRadioButton.md)
- [TRadioButtonList](./TRadioButtonList.md)
