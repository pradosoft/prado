# Data/ActiveRecord/TActiveRecordInvalidFinderResult

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [ActiveRecord](./INDEX.md) / **`TActiveRecordInvalidFinderResult`**

## Class Info
**Location:** `framework/Data/ActiveRecord/TActiveRecordInvalidFinderResult.php`
**Namespace:** `Prado\Data\ActiveRecord`

## Overview
`TActiveRecordInvalidFinderResult` defines what happens when a finder method returns no results.

## Constants

- `Null` - Return null (default)
- `Exception` - Throw a [`TActiveRecordException`](./TActiveRecordException.md)

## Usage

```php
TActiveRecordManager::getInstance()->setInvalidFinderResult(TActiveRecordInvalidFinderResult::Exception);
```

## See Also

- [TActiveRecordManager](./TActiveRecordManager.md) - Manager class