# Web/UI/ActiveControls/TActiveDataList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveDataList`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveDataList.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Data-bound list control using callbacks instead of postbacks. Uses span container for client-side content replacement. Supports automatic pager rendering when data source changes.

## Key Properties/Methods

- `setDataSource($value)` - Sets data source and triggers pager rendering
- `getContainerID()` - Returns container span ID
- `render($writer)` - Renders datalist with deferred rendering support
- `renderPager()` - Renders connected pagers
- `getActiveControl()` - Returns [TBaseActiveControl](./TBaseActiveControl.md) options

## See Also

- `TDataList`, [TActivePager](./TActivePager.md), [IActiveControl](./IActiveControl.md)
