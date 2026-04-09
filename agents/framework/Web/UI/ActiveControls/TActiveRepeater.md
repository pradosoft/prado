# TActiveRepeater

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveRepeater](./TActiveRepeater.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveRepeater.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TRepeater for data-bound lists using callbacks instead of postbacks. Supports surrounding container tag configuration and automatic pager rendering when data source changes.

## Key Properties/Methods

- `setDataSource($value)` - Sets data source and triggers pager rendering
- `getSurroundingTag()` / `setSurroundingTag($value)` - Container tag (default 'div')
- `getSurroundingTagID()` - Returns container element ID
- `render($writer)` - Renders repeater with deferred rendering support

## See Also

- `TRepeater`, [TActivePager](./TActivePager.md), `ISurroundable`
