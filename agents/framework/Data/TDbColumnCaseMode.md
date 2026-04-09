# TDbColumnCaseMode

### Directories

[./](../INDEX.md) > [Data](./INDEX.md) > [TDbColumnCaseMode](./TDbColumnCaseMode.md)

**Location:** `framework/Data/TDbColumnCaseMode.php`
**Namespace:** `Prado\Data`

## Overview

`TDbColumnCaseMode` is an enumerable class that specifies how column names should be handled when reading from the database.

## Constants

- `Preserved` - Column names are kept as-is from the database
- `LowerCase` - Column names are converted to lowercase
- `UpperCase` - Column names are converted to uppercase

## Usage

```php
TDbColumnCaseMode::Preserved;   // Keep original case
TDbColumnCaseMode::LowerCase;   // Convert to lowercase
TDbColumnCaseMode::UpperCase;   // Convert to uppercase
```

## See Also

- [TDbConnection](./TDbConnection.md) - Uses this for column name handling