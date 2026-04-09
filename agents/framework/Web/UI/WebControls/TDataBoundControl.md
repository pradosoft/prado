# TDataBoundControl

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TDataBoundControl](./TDataBoundControl.md)

**Location:** `framework/Web/UI/WebControls/TDataBoundControl.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TDataBoundControl is the base class for controls that populate data from data sources. Provides paging capabilities and data source management.

## Key Properties/Methods

- `DataSource` / `DataSourceID` - Data source object or ID
- `DataMember` - Name of data view to use
- `AllowPaging` - Enable paging support
- `PageSize` - Items per page
- `CurrentPageIndex` - Zero-based current page index
- `PageCount` - Total number of pages
- `AllowCustomPaging` / `VirtualItemCount` - Custom paging support
- `dataBind()` - Performs databinding

## See Also

- [TWebControl](./TWebControl.md)
