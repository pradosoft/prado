# TRepeaterItemRenderer

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TRepeaterItemRenderer](./TRepeaterItemRenderer.md)

**Location:** `framework/Web/UI/WebControls/TRepeaterItemRenderer.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TRepeaterItemRenderer is a convenient base class for defining item renderers specific to TRepeater. It extends TItemDataRenderer and implements event bubbling for the OnCommand event, wrapping command parameters with item information.

## Key Properties/Methods

- `bubbleEvent($sender, $param)` - Wraps command events with TRepeaterCommandEventParameter

## See Also

- [TItemDataRenderer](./TItemDataRenderer.md)
- [TRepeater](./TRepeater.md)
