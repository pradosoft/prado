# TDataSourceView

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TDataSourceView](./TDataSourceView.md)

**Location:** `framework/Web/UI/WebControls/TDataSourceView.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TDataSourceView is an abstract base class for data views. Provides select, insert, update, and delete operations.

## Key Properties/Methods

- `select()` - Abstract method to perform data selection
- `insertAt()` - Insert a record
- `update()` - Update records with keys and values
- `delete()` - Delete records by keys
- `getCanDelete()` / `getCanInsert()` / `getCanPage()` / etc. - Capability flags
- `getName()` - View name
- `getDataSource()` - Owner data source control

## See Also

- [IDataSource](./IDataSource.md)
